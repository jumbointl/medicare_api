<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppointmentModel;
use App\Services\AgoraJoinDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AgoraVideoController extends Controller
{
    public function __construct(
        private AgoraJoinDataService $agoraJoinDataService
    ) {}

    public function getJoinData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => ['required', 'integer', 'exists:appointments,id'],
            'user_id' => ['required', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'response' => 400,
                'status' => false,
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $appointment = AppointmentModel::where('id', $request->appointment_id)->first();

            if (!$appointment) {
                return response()->json([
                    'response' => 404,
                    'status' => false,
                    'message' => 'Appointment no encontrado.',
                ], 404);
            }

            $result = $this->agoraJoinDataService->buildJoinData(
                $appointment,
                (int) $request->user_id
            );

            return response()->json($result, $result['http_code'] ?? 200);
        } catch (\Throwable $e) {
            report($e);

            $code = (int) $e->getCode();
            if (!in_array($code, [400, 403, 404, 500], true)) {
                $code = 500;
            }

            return response()->json([
                'response' => $code,
                'status' => false,
                'message' => $e->getMessage() ?: 'No se pudo generar join-data.',
            ], $code);
        }
    }
}