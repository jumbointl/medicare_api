<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppointmentInvoiceModel;
use App\Models\AllTransactionModel;
use App\Models\AppointmentPaymentModel;
use App\Models\AppointmentInvoiceItemModel;
use App\Models\AppointmentModel;
use App\Http\Controllers\Api\V1\AppointmentCheckinController;
use PDF;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;

class AppointmentInvoiceController extends Controller
{
    protected function resolvePaymentStatusByType(?int $idPaymentType): string
    {
        if (is_null($idPaymentType)) {
            return 'Unpaid';
        }

        return $idPaymentType >= 8000 ? 'Paid' : 'Unpaid';
    }

    protected function resolveAggregateAppointmentPaymentStatus($appointmentId): string
    {
        $appointmentInvoices = AppointmentInvoiceModel::where("appointment_id", $appointmentId)->get();

        $paymentStatus = 'Partially Paid';

        $allPaid = true;
        foreach ($appointmentInvoices as $invoice) {
            if ($invoice->status !== "Paid") {
                $allPaid = false;
                break;
            }
        }
        if ($allPaid) {
            return "Paid";
        }

        $allUnpaid = true;
        foreach ($appointmentInvoices as $invoice) {
            if ($invoice->status !== "Unpaid") {
                $allUnpaid = false;
                break;
            }
        }
        if ($allUnpaid) {
            return "Unpaid";
        }

        return $paymentStatus;
    }

    function addData(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'clinic_id' => 'required',
            'total_amount' => 'required',
            'invoce_items' => 'required|array|min:1',
            'invoce_items.*.total_price' => 'required',
            'invoce_items.*.service_id' => 'required',
            'id_payment_type' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response(["response" => 400, "message" => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $timeStamp = date("Y-m-d H:i:s");

            $idPaymentType = isset($request->id_payment_type) ? (int) $request->id_payment_type : null;

            if (is_null($idPaymentType) && isset($request->status)) {
                $resolvedStatus = $request->status === 'Paid' ? 'Paid' : 'Unpaid';
            } else {
                $resolvedStatus = $this->resolvePaymentStatusByType($idPaymentType);
            }

            $dataModel = new AppointmentInvoiceModel;
            $dataModel->user_id = $request->user_id;
            $dataModel->clinic_id = $request->clinic_id;
            $dataModel->patient_id = $request->patient_id;
            $dataModel->appointment_id = $request->appointment_id;
            $dataModel->lab_booking_id = $request->lab_booking_id;
            $dataModel->pathology_id = $request->pathology_id;
            $dataModel->status = $resolvedStatus;
            $dataModel->coupon_title = $request->coupon_title;
            $dataModel->coupon_value = $request->coupon_value;
            $dataModel->coupon_off_amount = $request->coupon_off_amount;
            $dataModel->coupon_off_amount = $request->coupon_off_amount;
            $dataModel->total_amount = $request->total_amount;
            $dataModel->invoice_date = now();
            $dataModel->created_at = $timeStamp;
            $dataModel->updated_at = $timeStamp;
            $qResponce = $dataModel->save();

            if ($qResponce) {
                foreach ($request->invoce_items as $item) {
                    $dataInvoiceItemModel = new AppointmentInvoiceItemModel;
                    $dataInvoiceItemModel->invoice_id = $dataModel->id;
                    $dataInvoiceItemModel->description = $item['description'] ?? '';
                    $dataInvoiceItemModel->quantity = $item['quantity'] ?? 1;
                    $dataInvoiceItemModel->clinic_id = $request->clinic_id;
                    $dataInvoiceItemModel->unit_price = $item['unit_price'] ?? 0;
                    $dataInvoiceItemModel->service_charge = $item['service_charge'] ?? 0;
                    $dataInvoiceItemModel->service_id = $item['service_id'] ?? null;
                    $dataInvoiceItemModel->pathology_id = $item['pathology_id'] ?? null;
                    $dataInvoiceItemModel->total_price = $item['total_price'] ?? 0;
                    $dataInvoiceItemModel->unit_tax = $item['unit_tax'] ?? 0;
                    $dataInvoiceItemModel->is_tax_included = $item['is_tax_included'] ?? 0;
                    $dataInvoiceItemModel->unit_tax_amount = $item['unit_tax_amount'] ?? 0;
                    $dataInvoiceItemModel->created_at = $timeStamp;
                    $dataInvoiceItemModel->updated_at = $timeStamp;
                    $dataInvoiceItemModel->save();
                }
            }

            if ($resolvedStatus == "Paid") {
                $dataTXNModel = new AllTransactionModel;
                $dataTXNModel->amount = $dataModel->total_amount;
                $dataTXNModel->user_id = $dataModel->user_id;
                $dataTXNModel->patient_id = $dataModel->patient_id;
                $dataTXNModel->clinic_id = $dataModel->clinic_id;
                $dataTXNModel->appointment_id = $dataModel->appointment_id;
                $dataTXNModel->payment_transaction_id = $request->payment_transaction_id;
                $dataTXNModel->transaction_type = "Debited";
                $dataTXNModel->created_at = now();
                $dataTXNModel->updated_at = now();
                $dataTXNModel->save();

                $dataPaymentModel = new AppointmentPaymentModel;
                $dataPaymentModel->txn_id = $dataTXNModel->id;
                $dataPaymentModel->invoice_id = $dataModel->id;
                $dataPaymentModel->amount = $dataModel->total_amount;
                $dataPaymentModel->payment_time_stamp = now();
                $dataPaymentModel->clinic_id = $dataModel->clinic_id;
                $dataPaymentModel->payment_method = $request->payment_method;
                $dataPaymentModel->created_at = now();
                $dataPaymentModel->updated_at = now();
                $dataPaymentModel->save();
            }

            if ($dataModel->appointment_id) {
                $paymentStatus = $this->resolveAggregateAppointmentPaymentStatus($dataModel->appointment_id);

                $appointmentModel = AppointmentModel::find($dataModel->appointment_id);
                if ($appointmentModel) {
                    $appointmentModel->payment_status = $paymentStatus;
                    $appointmentModel->save();
                }
            }

            DB::commit();
            return Helpers::successWithIdResponse("successfully", $dataModel->id);
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error $e");
        }
    }

    function markPaid(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'id' => 'required',
            'amount' => 'required'
        ]);

        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        try {
            DB::beginTransaction();
            $dataModel = AppointmentInvoiceModel::where("id", $request->id)->first();
            if (!$dataModel) {
                DB::rollBack();
                return Helpers::errorResponse("Invoice not found");
            }
            if ($dataModel->status == "Paid") {
                DB::rollBack();
                return Helpers::errorResponse("Invoice already paid");
            }

            $dataTXNModel = new AllTransactionModel;
            $dataTXNModel->amount = $request->amount;
            $dataTXNModel->user_id = $dataModel->user_id;
            $dataTXNModel->patient_id = $dataModel->patient_id;
            $dataTXNModel->clinic_id = $dataModel->clinic_id;
            $dataTXNModel->lab_booking_id = $dataModel->lab_booking_id;
            $dataTXNModel->pathologist_id = $dataModel->pathology_id;
            $dataTXNModel->appointment_id = $dataModel->appointment_id;
            $dataTXNModel->transaction_type = "Debited";
            $dataTXNModel->created_at = now();
            $dataTXNModel->updated_at = now();
            $dataTXNModel->save();

            $dataPaymentModel = new AppointmentPaymentModel;
            $dataPaymentModel->txn_id = $dataTXNModel->id;
            $dataPaymentModel->invoice_id = $request->id;
            $dataPaymentModel->amount = $request->amount;
            $dataPaymentModel->payment_time_stamp = now();
            $dataPaymentModel->clinic_id = $dataModel->clinic_id;
            $dataPaymentModel->pathology_id = $dataModel->pathology_id;
            $dataPaymentModel->payment_method = $request->payment_method;
            $dataPaymentModel->created_at = now();
            $dataPaymentModel->updated_at = now();
            $dataPaymentModel->save();

            $totalPaid = AppointmentPaymentModel::where('invoice_id', $dataModel->id)
                ->sum('amount');

            $paymentStatus = ($totalPaid >= $dataModel->total_amount)
                ? "Paid"
                : "Partially Paid";

            $dataModel->status = $paymentStatus;
            $dataModel->updated_at = now();
            $dataModel->save();

            if ($dataModel->appointment_id) {
                $paymentStatus = $this->resolveAggregateAppointmentPaymentStatus($dataModel->appointment_id);

                $appointmentModel = AppointmentModel::find($dataModel->appointment_id);
                if ($appointmentModel) {
                    $appointmentModel->payment_status = $paymentStatus;
                    $appointmentModel->save();
                }
            }

            DB::commit();
            return response([
                "response" => 200,
                "status" => true,
                "message" => "Payment processed successfully",
                "invoice_status" => $paymentStatus,
                "invoice_id" => $dataModel->id,
                "total_paid" => $totalPaid,
                "data" => $responseData ?? null
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error");
        }
    }

    public function generatePDFForLab($id)
    {
        $invoice = DB::table("appointment_invoice")
            ->select(
                'appointment_invoice.*',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'patients.phone as patient_phone',
                'users.f_name as user_f_name',
                'users.l_name as user_l_name',
                'users.phone as user_phone',
            )
            ->LeftJoin('patients', 'patients.id', '=', 'appointment_invoice.patient_id')
            ->LeftJoin('users', 'users.id', '=', 'appointment_invoice.user_id')
            ->where("appointment_invoice.id", "=", $id)
            ->first();

        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        $invoiceItems = DB::table("appointments_invoice_item")
            ->select(
                'appointments_invoice_item.*',
                'services.title as service_title'
            )
            ->where("appointments_invoice_item.invoice_id", "=", $id)
            ->Leftjoin('services', 'services.id', '=', 'appointments_invoice_item.service_id')
            ->get();
        $invoice->items = $invoiceItems;

        $invoice->payments = DB::table("appointment_payments")
            ->select('*')
            ->where('invoice_id', $invoice->id)
            ->orderBy('payment_time_stamp', 'ASC')
            ->get();

        $pathDetails = DB::table('pathologist')
            ->where('pathologist.id', '=', $invoice->pathology_id)
            ->first();

        $invoice->clinic_name = $pathDetails->title ?? "--";
        $invoice->logo = $pathDetails->image ?? "--";
        $invoice->phone = $pathDetails->phone ?? "--";
        $invoice->phone_second = $pathDetails->phone_second ?? "--";
        $invoice->email = $pathDetails->email ?? "--";
        $invoice->address = $pathDetails->address ?? "--";

        $settings = DB::table('configurations')->pluck('value', 'id_name');

        $pdf = PDF::loadView('invoice.lab_pdf', ['invoice' => $invoice, 'settings' => $settings]);
        return $pdf->stream('lab.pdf', ['Attachment' => false]);
    }

    public function generatePDF($id)
    {
        $invoice = DB::table("appointment_invoice")
            ->select(
                'appointment_invoice.*',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'patients.phone as patient_phone',
                'users.f_name as user_f_name',
                'users.l_name as user_l_name',
                'users.phone as user_phone',
            )
            ->LeftJoin('patients', 'patients.id', '=', 'appointment_invoice.patient_id')
            ->LeftJoin('users', 'users.id', '=', 'appointment_invoice.user_id')
            ->where("appointment_invoice.id", "=", $id)
            ->first();

        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        $invoiceItems = DB::table("appointments_invoice_item")
            ->select(
                'appointments_invoice_item.*',
                'services.title as service_title'
            )
            ->where("appointments_invoice_item.invoice_id", "=", $id)
            ->Leftjoin('services', 'services.id', '=', 'appointments_invoice_item.service_id')
            ->get();
        $invoice->items = $invoiceItems;

        $invoice->payments = DB::table("appointment_payments")
            ->select(
                'id',
                'txn_id',
                'amount',
                'payment_method',
                'payment_time_stamp',
                'created_at'
            )
            ->where('invoice_id', $invoice->id)
            ->orderBy('payment_time_stamp', 'ASC')
            ->get();

        $clinicsDetails = DB::table('clinics')
            ->where('id', '=', $invoice->clinic_id)
            ->first();

        $invoice->clinic_name = $clinicsDetails->title ?? "--";
        $invoice->logo = $clinicsDetails->image ?? "--";
        $invoice->phone = $clinicsDetails->phone ?? "--";
        $invoice->phone_second = $clinicsDetails->phone_second ?? "--";
        $invoice->email = $clinicsDetails->email ?? "--";
        $invoice->address = $clinicsDetails->address ?? "--";

        $settings = DB::table('configurations')->pluck('value', 'id_name');

        $pdf = PDF::loadView('invoice.pdf', ['invoice' => $invoice, 'settings' => $settings]);
        return $pdf->stream('invoice.pdf', ['Attachment' => false]);
    }

    function getDataByLabAppId($id)
    {
        $data = DB::table("appointment_invoice")
            ->select(
                'appointment_invoice.*',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'users.f_name as user_f_name',
                'users.l_name as user_l_name'
            )
            ->LeftJoin('patients', 'patients.id', '=', 'appointment_invoice.patient_id')
            ->LeftJoin('users', 'users.id', '=', 'appointment_invoice.user_id')
            ->Where('appointment_invoice.lab_booking_id', '=', $id)
            ->OrderBy('appointment_invoice.created_at', 'DESC')
            ->first();

        if ($data != null) {
            $data->items = DB::table("appointments_invoice_item")
                ->select(
                    'appointments_invoice_item.*',
                    'services.title as service_title'
                )
                ->Where('appointments_invoice_item.invoice_id', '=', $data->id)
                ->Leftjoin('services', 'services.id', '=', 'appointments_invoice_item.service_id')
                ->OrderBy('appointments_invoice_item.created_at', 'ASC')
                ->get();
        }

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }

    function getDataByAppId($id)
    {
        $data = DB::table("appointment_invoice")
            ->select(
                'appointment_invoice.*',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'users.f_name as user_f_name',
                'users.l_name as user_l_name'
            )
            ->LeftJoin('patients', 'patients.id', '=', 'appointment_invoice.patient_id')
            ->LeftJoin('users', 'users.id', '=', 'appointment_invoice.user_id')
            ->Where('appointment_invoice.appointment_id', '=', $id)
            ->OrderBy('appointment_invoice.created_at', 'DESC')
            ->first();

        if ($data != null) {
            $data->items = DB::table("appointments_invoice_item")
                ->select(
                    'appointments_invoice_item.*',
                    'services.title as service_title'
                )
                ->Where('appointments_invoice_item.invoice_id', '=', $data->id)
                ->Leftjoin('services', 'services.id', '=', 'appointments_invoice_item.service_id')
                ->OrderBy('appointments_invoice_item.created_at', 'ASC')
                ->get();
        }

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }

    function getDataById($id)
    {
        $data = DB::table("appointment_invoice")
            ->select(
                'appointment_invoice.*',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'users.f_name as user_f_name',
                'users.l_name as user_l_name'
            )
            ->leftJoin('patients', 'patients.id', '=', 'appointment_invoice.patient_id')
            ->leftJoin('users', 'users.id', '=', 'appointment_invoice.user_id')
            ->where('appointment_invoice.id', $id)
            ->orderBy('appointment_invoice.created_at', 'DESC')
            ->first();

        if ($data) {
            $data->items = DB::table("appointments_invoice_item")
                ->select(
                    'appointments_invoice_item.*',
                    'services.title as service_title'
                )
                ->leftJoin('services', 'services.id', '=', 'appointments_invoice_item.service_id')
                ->where('appointments_invoice_item.invoice_id', $data->id)
                ->orderBy('appointments_invoice_item.created_at', 'ASC')
                ->get();

            $data->payments = DB::table("appointment_payments")
                ->select(
                    'id',
                    'txn_id',
                    'amount',
                    'payment_method',
                    'payment_time_stamp',
                    'created_at'
                )
                ->where('invoice_id', $data->id)
                ->orderBy('payment_time_stamp', 'ASC')
                ->get();

            $data->payment_summary = [
                'total_paid' => $data->payments->sum('amount'),
                'payment_count' => $data->payments->count(),
                'last_payment_at' => $data->payments->last()->payment_time_stamp ?? null,
            ];
        }

        return response([
            "response" => 200,
            "data" => $data
        ], 200);
    }

    public function getData(Request $request)
    {
        $query = DB::table("appointment_invoice")
            ->select(
                'appointment_invoice.*',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name',
                'users.f_name as user_f_name',
                'users.l_name as user_l_name',
                'appointments.doct_id'
            )
            ->leftJoin('patients', 'patients.id', '=', 'appointment_invoice.patient_id')
            ->leftJoin('users', 'users.id', '=', 'appointment_invoice.user_id')
            ->leftJoin('appointments', 'appointments.id', '=', 'appointment_invoice.appointment_id')
            ->orderBy('appointment_invoice.created_at', 'DESC');

        if ($request->filled('doctor_id')) {
            $query->where('appointments.doct_id', $request->doctor_id);
        }

        if ($request->filled('clinic_id')) {
            $query->where('appointment_invoice.clinic_id', $request->clinic_id);
        }

        if ($request->filled('pathology_id')) {
            $query->where('appointment_invoice.pathology_id', $request->pathology_id);
        }

        if ($request->filled('appointment_id')) {
            $query->where('appointment_invoice.appointment_id', $request->appointment_id);
        }

        if ($request->filled('patient_id')) {
            $query->where('appointment_invoice.patient_id', $request->patient_id);
        }

        if ($request->filled('lab_booking_id')) {
            $query->where('appointment_invoice.lab_booking_id', $request->lab_booking_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('appointment_invoice.invoice_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('appointment_invoice.invoice_date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw('CONCAT(patients.f_name, " ", patients.l_name) LIKE ?', ["%$search%"])
                    ->orWhereRaw('CONCAT(users.f_name, " ", users.l_name) LIKE ?', ["%$search%"])
                    ->orWhere('appointment_invoice.id', 'like', "%$search%")
                    ->orWhere('appointment_invoice.user_id', 'like', "%$search%")
                    ->orWhere('appointment_invoice.patient_id', 'like', "%$search%")
                    ->orWhere('appointment_invoice.appointment_id', 'like', "%$search%")
                    ->orWhere('appointment_invoice.status', 'like', "%$search%")
                    ->orWhere('appointment_invoice.total_amount', 'like', "%$search%")
                    ->orWhere('appointment_invoice.invoice_date', 'like', "%$search%");
            });
        }

        $total_record = $query->count();

        if ($request->filled(['start', 'end'])) {
            $query->skip($request->start)->take($request->end - $request->start);
        }

        $data = $query->get();

        if ($request->filled('is_show_item') && $request->is_show_item == 1) {
            foreach ($data as $dataItem) {
                $dataItem->items = DB::table("appointments_invoice_item")
                    ->select(
                        'appointments_invoice_item.*',
                        'services.title as service_title'
                    )
                    ->Where('appointments_invoice_item.invoice_id', '=', $dataItem->id)
                    ->Leftjoin('services', 'services.id', '=', 'appointments_invoice_item.service_id')
                    ->OrderBy('appointments_invoice_item.created_at', 'ASC')
                    ->get();
            }
        }

        return response()->json([
            "response" => 200,
            "total_record" => $total_record,
            "data" => $data,
        ], 200);
    }

    function deleteData(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);

        if ($validator->fails()) {
            return response(["response" => 400], 400);
        }

        try {
            DB::beginTransaction();
            $dataModel = AppointmentInvoiceModel::where("id", $request->id)->first();
            $appointmentId = $dataModel->appointment_id;

            if ($dataModel->status == "Unpaid") {
                $dataModel->delete();
            } else {
                DB::rollBack();
                return Helpers::errorResponse("Only Unpaid Invocie can be deleted");
            }

            if ($appointmentId) {
                $paymentStatus = $this->resolveAggregateAppointmentPaymentStatus($dataModel->appointment_id);

                $appointmentModel = AppointmentModel::find($dataModel->appointment_id);
                if ($appointmentModel) {
                    $appointmentModel->payment_status = $paymentStatus;
                    $appointmentModel->save();
                }
            }

            DB::commit();
            return Helpers::successResponse("successfully Deleted");
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("This record cannot be deleted because it is linked to multiple data entries in the system. You can only deactivate it to prevent future use.");
        }
    }
}