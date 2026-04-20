<?php

namespace App\Http\Controllers\Api\V1\DoctorWeb;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorWebReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = DB::table('doctors_review')
            ->select(
                'doctors_review.*',
                'patients.f_name as patient_f_name',
                'patients.l_name as patient_l_name'
            )
            ->join('patients', 'patients.id', '=', 'doctors_review.user_id')
            ->where('doctors_review.doctor_id', $user->id)
            ->orderByDesc('doctors_review.created_at');

        return response()->json([
            'status' => true,
            'data' => $query->paginate(20),
        ], 200);
    }
}