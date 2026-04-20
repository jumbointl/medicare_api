<?php

namespace App\Http\Controllers\Api\V1\DoctorWeb;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorWebNotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $data = DB::table('doctor_notification')
            ->where('doctor_id', $user->id)
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $data,
        ], 200);
    }

    public function markSeen(Request $request, $id)
    {
        $user = $request->user();

        $updated = DB::table('doctor_notification')
            ->where('id', (int) $id)
            ->where('doctor_id', $user->id)
            ->update([
                'seen_status' => 1,
                'updated_at' => now(),
            ]);

        return response()->json([
            'status' => $updated > 0,
            'message' => $updated > 0 ? 'Notification marked as seen.' : 'Notification not found.',
        ], 200);
    }
}