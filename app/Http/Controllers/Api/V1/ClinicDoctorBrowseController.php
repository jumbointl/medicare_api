<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClinicDoctorBrowseController extends Controller
{
    private function resolveLimit(Request $request): int
    {
        $defaultLimit = (int) config('app_limits.default', 20);
        $maxLimit = (int) config('app_limits.max', 100);

        $requestedLimit = (int) $request->get('limit', $defaultLimit);

        if ($requestedLimit <= 0) {
            return $defaultLimit;
        }

        return min($requestedLimit, $maxLimit);
    }

    public function getCityClinics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'required|integer',
            'limit' => 'nullable|integer|min:1',
            'random' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => [],
            ], 422);
        }

        $cityId = (int) $request->city_id;
        $limit = $this->resolveLimit($request);
        $random = filter_var($request->get('random', false), FILTER_VALIDATE_BOOLEAN);

        $query = DB::table('view_city_clinics')
            ->where('city_id', $cityId)
            ->where('clinic_active', 1)
            ->where('doctor_count', '>', 0);

        if ($random) {
            $query->inRandomOrder();
        } else {
            $query->orderBy('clinic_title');
        }

        $rows = $query->limit($limit)->get();

        return response()->json([
            'status' => true,
            'message' => 'Successfully',
            'data' => $rows,
            'meta' => [
                'city_id' => $cityId,
                'limit' => $limit,
                'random' => $random,
            ],
        ], 200);
    }

    public function getClinicDoctors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clinic_id' => 'required|integer',
            'doctor_id' => 'nullable|integer',
            'limit' => 'nullable|integer|min:1',
            'random' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => [],
            ], 422);
        }

        $clinicId = (int) $request->clinic_id;
        $doctorId = $request->filled('doctor_id') ? (int) $request->doctor_id : null;

        $limit = $this->resolveLimit($request);
        $random = filter_var($request->get('random', false), FILTER_VALIDATE_BOOLEAN);

        $query = DB::table('view_clinic_doctors')
            ->where('clinic_id', $clinicId)
            ->where('is_active', 1);

        // When doctor_id is provided, get only that doctor inside this clinic.
        if (!is_null($doctorId)) {
            $query->where('doctor_id', $doctorId);
        }

        if ($random && is_null($doctorId)) {
            $query->inRandomOrder();
        } else {
            $query->orderBy('doctor_name');
        }

        $rows = $query->limit($limit)->get();

        return response()->json([
            'status' => true,
            'message' => 'Successfully',
            'data' => $rows,
            'meta' => [
                'clinic_id' => $clinicId,
                'doctor_id' => $doctorId,
                'limit' => $limit,
                'random' => $random,
            ],
        ], 200);
    }

    public function getCityClinicsWithDoctors(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city_id' => 'required|integer',
            'limit' => 'nullable|integer|min:1',
            'random' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'data' => [],
            ], 422);
        }

        $cityId = (int) $request->city_id;
        $limit = $this->resolveLimit($request);
        $random = filter_var($request->get('random', false), FILTER_VALIDATE_BOOLEAN);

        $query = DB::table('view_city_clinics_with_doctors')
            ->where('city_id', $cityId)
            ->where('is_active', 1);

        if ($random) {
            $query->inRandomOrder();
        } else {
            $query->orderBy('clinic_title')->orderBy('doctor_name');
        }

        $rows = $query->limit($limit)->get();

        return response()->json([
            'status' => true,
            'message' => 'Successfully',
            'data' => $rows,
            'meta' => [
                'city_id' => $cityId,
                'limit' => $limit,
                'random' => $random,
            ],
        ], 200);
    }
}