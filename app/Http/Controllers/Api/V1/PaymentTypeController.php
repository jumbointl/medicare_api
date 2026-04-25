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
            ->select('id', 'name', 'active');

        if ($request->has('active')) {
            $query->where('active', (int) $request->active);
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