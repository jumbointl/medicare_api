<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServicesModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServicesController extends Controller
{
    //add new data
    function addData(Request $request)
    {


        $validator = Validator::make(request()->all(), [
            'title' => 'required',
            'base_price' => 'required'
    
        ]);
        if ($validator->fails())
            return response(["response" => 400, "error" => $validator->errors()], 400);



        try {
            //check of clinic id or lab id
            if (!isset($request->clinic_id) && !isset($request->lab_id)) {
                return Helpers::errorResponse("clinic id or lab id is required");
            } else if (isset($request->clinic_id) && !isset($request->lab_id)) {
                $alreadyAddedModel = ServicesModel::where("clinic_id", $request->clinic_id)->where("title", $request->title)->first();
                if ($alreadyAddedModel) {
                    return Helpers::errorResponse("Title already exists");
                }
            } else if (!isset($request->clinic_id) && isset($request->lab_id)) {
                $alreadyAddedModel = ServicesModel::where("lab_id", $request->lab_id)->where("title", $request->title)->first();
                if ($alreadyAddedModel) {
                    return Helpers::errorResponse("Title already exists");
                }
            }


            DB::beginTransaction();

            $timeStamp = date("Y-m-d H:i:s");
            $dataModel = new ServicesModel;

            $dataModel->title = $request->title;

            $dataModel->base_price = $request->base_price;

            $dataModel->clinic_id = $request->clinic_id ?? null;
            $dataModel->lab_id = $request->lab_id ?? null;
        

            $dataModel->created_at = $timeStamp;
            $dataModel->updated_at = $timeStamp;
            $qResponce = $dataModel->save();

            // if(isset($request->service_charges)){
            //      if($qResponce && is_array($request->service_charges)) {
            //         foreach($request->service_charges as $serviceCharge) {
            //             if(isset($serviceCharge['service_charge_id'])) {
            //                 // Insert service tax relationship
            //                 DB::table('service_has_charges')->insert([
            //                     'service_id' => $dataModel->id,
            //                     'service_charge_id' => $serviceCharge['service_charge_id'],
            //                     'created_at' => $timeStamp,
            //                     'updated_at' => $timeStamp
            //                 ]);
            //             }
            //         }
            //     }
            // }

            if (isset($request->service_taxes)) {
                if ($qResponce && is_array($request->service_taxes)) {
                    foreach ($request->service_taxes as $serviceTax) {
                        if (isset($serviceTax['tax_id'])) {
                            // Insert service tax relationship
                            DB::table('service_tax')->insert([
                                'service_id' => $dataModel->id,
                                'tax_id' => $serviceTax['tax_id'],
                                'created_at' => $timeStamp,
                                'updated_at' => $timeStamp
                            ]);
                        }
                    }
                }
            }




            if ($qResponce) {
                DB::commit();

                return Helpers::successWithIdResponse("successfully", $dataModel->id);
            } else {
                DB::rollBack();

                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return Helpers::errorResponse("error $e");
        }
    }
    // Update Deapartment
    function updateData(Request $request)
    {

        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {

            $dataModel = ServicesModel::where("id", $request->id)->first();


            DB::beginTransaction();

            if (isset($request->name)) {

                if (isset($dataModel->clinic_id) && !isset($dataModel->lab_id)) {
                    $alreadyAddedModel = ServicesModel::where("clinic_id", $dataModel->clinic_id)->where("title", $dataModel->title)->where('id', "!=", $request->id)->first();
                    if ($alreadyAddedModel) {
                        return Helpers::errorResponse("Title already exists");
                    }
                } else if (!isset($dataModel->clinic_id) && isset($dataModel->lab_id)) {
                    $alreadyAddedModel = ServicesModel::where("lab_id", $dataModel->lab_id)->where("title", $dataModel->title)->where('id', "!=", $request->id)->first();
                    if ($alreadyAddedModel) {
                        return Helpers::errorResponse("Title already exists");
                    }
                }
                $dataModel->title = $request->title;
            }
            if (isset($request->base_price)) {
                $dataModel->base_price = $request->base_price;
            }

            if (isset($request->active)) {
                $dataModel->active = $request->active;
            }



            $timeStamp = date("Y-m-d H:i:s");
            $dataModel->updated_at = $timeStamp;
            $qResponce = $dataModel->save();
            if ($qResponce) {

                //      if(isset($request->service_charges)){
                //         // First, delete existing relationships
                //         DB::table('service_has_charges')->where('service_id', $dataModel->id)->delete();
                //            if($qResponce && is_array($request->service_charges)) {
                //         foreach($request->service_charges as $serviceCharge) {
                //             if(isset($serviceCharge['service_charge_id'])) {
                //                 // Insert service tax relationship
                //                 DB::table('service_has_charges')->insert([
                //                     'service_id' => $dataModel->id,
                //                     'service_charge_id' => $serviceCharge['service_charge_id'],
                //                     'created_at' => $timeStamp,
                //                     'updated_at' => $timeStamp
                //                 ]);
                //             }
                //         }
                //     }
                // }

                if (isset($request->service_taxes)) {
                    if ($qResponce && is_array($request->service_taxes)) {
                        DB::table('service_tax')->where('service_id', $dataModel->id)->delete();
                        foreach ($request->service_taxes as $serviceTax) {
                            if (isset($serviceTax['tax_id'])) {
                                // Insert service tax relationship
                                DB::table('service_tax')->insert([
                                    'service_id' => $dataModel->id,
                                    'tax_id' => $serviceTax['tax_id'],
                                    'created_at' => $timeStamp,
                                    'updated_at' => $timeStamp
                                ]);
                            }
                        }
                    }
                }else{
                      DB::table('service_tax')->where('service_id', $dataModel->id)->delete();
                }

                DB::commit();
                return Helpers::successResponse("successfully");
            } else {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error $e");
        }
    }

    // get data


    public function getData(Request $request)
    {
        // Define the base query
        $query = DB::table("services")
            ->select(
                'services.*',
                'clinics.title as clinic_title',
                'pathologist.title as lab_title'

            )
            ->Leftjoin('clinics', 'clinics.id', '=', 'services.clinic_id')
            ->Leftjoin('pathologist', 'pathologist.id', '=', 'services.lab_id')
            ->orderBy("services.created_at", "DESC");

        // Apply filters (like search)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('services.title', 'like', '%' . $search . '%')
                    ->orWhere('services.base_price', 'like', '%' . $search . '%')
                    ->orWhere('clinics.title', 'like', '%' . $search . '%')
                    ->orWhere('pathologist.title', 'like', '%' . $search . '%');
            });
        }
        if ($request->has('clinic_id')) {
            $query->where('services.clinic_id', '=', $request->clinic_id);
        }
        if ($request->has('lab_id')) {
            $query->where('services.lab_id', '=', $request->lab_id);
        }

        if ($request->has('active')) {
            $query->where('services.active', '=', $request->active);
        }
        // Calculate total records first (without pagination)
        $total_record = $query->count(); // Count the total records before pagination

        // Apply pagination if start and end are provided
        if ($request->filled(['start', 'end'])) {
            $start = $request->start;
            $end = $request->end;
            $query->skip($start)->take($end - $start);
        }
        // Get the paginated data
        $data = $query->get();

        if ($data->isNotEmpty()) {
            foreach ($data as $item) {
                $service_taxes = DB::table('service_tax')
                    ->join('taxes', 'taxes.id', '=', 'service_tax.tax_id')
                    ->where('service_tax.service_id', $item->id)
                    ->select('taxes.*',)
                    ->get();
                $item->service_taxes = $service_taxes;
            }
        }

        // Return the response with total records and data
        $response = [
            "response" => 200,
            "total_record" => $total_record,
            'data' => $data,
        ];

        return response()->json($response, 200);
    }


    // get data by id
    function getDataById($id)
    {

        $data = DB::table("services")
            ->select(
                'services.*',
                'clinics.title as clinic_title',
                'pathologist.title as lab_title'


            )
            ->Leftjoin('clinics', 'clinics.id', '=', 'services.clinic_id')
            ->Leftjoin('pathologist', 'pathologist.id', '=', 'services.lab_id')
            ->where('services.id', '=', $id)
            ->first();

        if ($data) {


            $service_taxes = DB::table('service_tax')
                ->join('taxes', 'taxes.id', '=', 'service_tax.tax_id')
                ->where('service_tax.service_id', $data->id)
                ->select('taxes.*',)
                ->get();
            $data->service_taxes = $service_taxes;




            //    $data->service_charges = DB::table('service_has_charges')
            // ->join('service_charges', 'service_charges.id', '=', 'service_has_charges.service_charge_id')
            // ->where('service_has_charges.service_id', $data->id)
            // ->select('service_has_charges.*',
            //               'service_charges.name as service_charge_name',
            //     'service_charges.service_charge_type as service_charge_type',
            //     'service_charges.service_charge_value as service_charge_value',
            // )
            // ->get();
            // if($data->service_charges)
            // {
            //     foreach($data->service_charges as $serviceCharge) {
            //         $serviceCharge->service_taxes = DB::table('service_charge_tax')
            //             ->join('taxes', 'taxes.id', '=', 'service_charge_tax.tax_id')
            //             ->where('service_charge_tax.service_charge_id', $serviceCharge->service_charge_id)
            //             ->select('taxes.*')
            //             ->get();
            //     }
            // }

        }

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }

    function deleteData(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            $dataModel = ServicesModel::where("id", $request->id)->first();
            $qResponce = $dataModel->delete();
            if ($qResponce) {


                return Helpers::successResponse("successfully Deleted");
            } else
                return Helpers::errorResponse("error");
        } catch (\Exception $e) {

            return Helpers::errorResponse("This record cannot be deleted because it is linked to multiple data entries in the system. You can only deactivate it to prevent future use.");
        }
    }
}
