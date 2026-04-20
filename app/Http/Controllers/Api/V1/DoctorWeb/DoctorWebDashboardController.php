<?php

namespace App\Http\Controllers\Api\V1\DoctorWeb;

use App\Http\Controllers\Controller;
use App\Models\AppointmentModel;
use Illuminate\Http\Request;

class DoctorWebDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        $base = AppointmentModel::query()->where('doct_id', $user->id);

        return response()->json([
            'status' => true,
            'data' => [
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