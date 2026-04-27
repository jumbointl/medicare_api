<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TimeSlotsVideoModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimeSlotsVideoController extends Controller
{
    public function addData(Request $request)
    {
        $request->validate([
            'doct_id' => 'required|integer',
            'clinic_id' => 'required|integer',
            'day' => 'required|string',
            'time_start' => 'required',
            'time_end' => 'required',
            'time_duration' => 'required|integer|min:1',
        ]);

        $doctorId = (int) $request->doct_id;
        $clinicId = (int) $request->clinic_id;

        $doctor = DB::table('doctors')
            ->select('id', 'user_id')
            ->where('id', $doctorId)
            ->first();

        if (!$doctor) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor not found.',
            ], 422);
        }

        $doctorClinic = DB::table('user_clinics')
            ->where('user_id', $doctor->user_id)
            ->where('clinic_id', $clinicId)
            ->where('is_active', 1)
            ->exists();

        if (!$doctorClinic) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor does not belong to the selected clinic.',
            ], 422);
        }

        $slot = new TimeSlotsVideoModel();
        $slot->doct_id = $doctorId;
        $slot->clinic_id = $clinicId;
        $slot->day = $request->day;
        $slot->time_start = $request->time_start;
        $slot->time_end = $request->time_end;
        $slot->time_duration = (int) $request->time_duration;
        $slot->save();

        return response()->json([
            'status' => true,
            'message' => 'Video time slot created successfully.',
            'data' => $slot,
        ], 200);
    }

    public function deleteData(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        $slot = TimeSlotsVideoModel::find($request->id);

        if (!$slot) {
            return response()->json([
                'status' => false,
                'message' => 'Video time slot not found.',
            ], 404);
        }

        $slot->delete();

        return response()->json([
            'status' => true,
            'message' => 'Video time slot deleted successfully.',
        ], 200);
    }

    public function getData(Request $request)
    {
        $query = TimeSlotsVideoModel::query();

        if ($request->filled('doct_id')) {
            $query->where('doct_id', (int) $request->doct_id);
        }

        if ($request->filled('clinic_id')) {
            $query->where('clinic_id', (int) $request->clinic_id);
        }

        if ($request->filled('day')) {
            $query->where('day', $request->day);
        }

        $data = $query
            ->orderBy('day')
            ->orderBy('time_start')
            ->get();

        return response()->json([
            'response' => 200,
            'data' => $data,
        ], 200);
    }

    public function getDataById($id)
    {
        $data = TimeSlotsVideoModel::find($id);

        return response()->json([
            'response' => 200,
            'data' => $data,
        ], 200);
    }

    public function updateData(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'doct_id' => 'required|integer',
            'clinic_id' => 'required|integer',
            'day' => 'required|string',
            'time_start' => 'required',
            'time_end' => 'required',
            'time_duration' => 'required|integer|min:1',
        ]);

        $slot = TimeSlotsVideoModel::find($request->id);

        if (!$slot) {
            return response()->json([
                'status' => false,
                'message' => 'Video time slot not found.',
            ], 404);
        }

        $doctorId = (int) $request->doct_id;
        $clinicId = (int) $request->clinic_id;

        $doctor = DB::table('doctors')
            ->select('id', 'user_id')
            ->where('id', $doctorId)
            ->first();

        if (!$doctor) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor not found.',
            ], 422);
        }

        $doctorClinic = DB::table('user_clinics')
            ->where('user_id', $doctor->user_id)
            ->where('clinic_id', $clinicId)
            ->where('is_active', 1)
            ->exists();

        if (!$doctorClinic) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor does not belong to the selected clinic.',
            ], 422);
        }

        $slot->doct_id = $doctorId;
        $slot->clinic_id = $clinicId;
        $slot->day = $request->day;
        $slot->time_start = $request->time_start;
        $slot->time_end = $request->time_end;
        $slot->time_duration = (int) $request->time_duration;
        $slot->save();

        return response()->json([
            'status' => true,
            'message' => 'Video time slot updated successfully.',
            'data' => $slot,
        ], 200);
    }

    public function getDoctorClinicVideoSlots($doctorId, $clinicId)
    {
        $data = TimeSlotsVideoModel::query()
            ->where('doct_id', (int) $doctorId)
            ->where('clinic_id', (int) $clinicId)
            ->orderBy('day')
            ->orderBy('time_start')
            ->get();

        return response()->json([
            'response' => 200,
            'data' => $data,
        ], 200);
    }
    public function getDoctorClinicVideoTimeInterval($doctorId, $clinicId, $day)
    {
        $data = TimeSlotsVideoModel::query()
            ->where('doct_id', (int) $doctorId)
            ->where('clinic_id', (int) $clinicId)
            ->where('day', $day)
            ->orderBy('time_start', 'ASC')
            ->get();

        $slots = [];

        foreach ($data as $timeSlot) {
            $start_time = strtotime($timeSlot->time_start);
            $end_time = strtotime($timeSlot->time_end);
            $time_duration = (int) $timeSlot->time_duration;

            if ($time_duration <= 0) {
                continue;
            }

            $current_time = $start_time;

            while ($current_time <= $end_time) {
                $slot_start = date('H:i', $current_time);
                $current_time += $time_duration * 60;
                $slot_end = date('H:i', $current_time);

                if ($current_time <= $end_time) {
                    $slots[] = [
                        'time_start' => $slot_start,
                        'time_end' => $slot_end,
                    ];
                }
            }
        }

        return response()->json([
            'response' => 200,
            'data' => $slots,
        ], 200);
    }
}