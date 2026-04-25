<?php

namespace App\Http\Controllers\Api\V1\DoctorWeb;

use App\Http\Controllers\Controller;
use App\Models\AppointmentModel;
use App\Models\VUser;
use Illuminate\Http\Request;

class DoctorWebDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $vUser = VUser::query()->where('id', $user->id)->first();

        if (!$vUser || empty($vUser->doctor_id)) {
            return response()->json([
                'status' => false,
                'message' => 'Doctor profile not found.',
            ], 404);
        }

        $today = now()->toDateString();
        $clinicId = $request->query('clinic_id');

        $base = AppointmentModel::query()
            ->where('doct_id', $vUser->doctor_id);

        if (!empty($clinicId) && $clinicId !== 'all') {
            $base->where('clinic_id', $clinicId);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'total_appointments' => (clone $base)->count(),
                'today_total' => (clone $base)->whereDate('date', $today)->count(),
                'today_confirmed' => (clone $base)->whereDate('date', $today)->where('status', 'Confirmed')->count(),
                'pending' => (clone $base)->where('status', 'Pending')->count(),
                'upcoming_video' => (clone $base)
                    ->where('type', 'Video Consultant')
                    ->whereIn('status', ['Pending', 'Confirmed'])
                    ->count(),
                'completed_today' => (clone $base)
                    ->whereDate('date', $today)
                    ->whereIn('status', ['Completed', 'Visited'])
                    ->count(),
            ],
        ], 200);
    }
}