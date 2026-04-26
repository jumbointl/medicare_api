<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentTypeModel;
use Illuminate\Http\Request;

class PaymentTypeController extends Controller
{
    public function getData(Request $request)
    {
        $query = PaymentTypeModel::query()
            ->select('id', 'name', 'active', 'opd', 'video', 'emergency');

        if ($request->has('active')) {
            $query->where('active', (int) $request->active);
        }

        if ($request->has('opd')) {
            $query->where('opd', (int) $request->opd);
        }

        if ($request->has('video')) {
            $query->where('video', (int) $request->video);
        }

        if ($request->has('emergency')) {
            $query->where('emergency', (int) $request->emergency);
        }

        // For user app/web booking: only show payment type IDs below 9000.
        // If isUser is not sent, no restriction is applied.
        // Payment type IDs >= 9000 are considered already-paid/internal payment types.
        if ($request->boolean('isUser')) {
            $query->where('id', '<', 9000);
        }

        $data = $query
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'response' => 200,
            'data' => $data,
        ], 200);
    }
}