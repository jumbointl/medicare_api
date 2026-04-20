<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\WebhookLogModel;
use App\Models\WebhookCentrelizeDataLogmodel;
use App\Models\PreOrderModel;
use App\Http\Controllers\Api\V1\AllTransactionController;
use App\Http\Controllers\Api\V1\AppointmentController;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\V1\LabBookingController;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
  public function handleWebhookStripe(Request $request)
  {
    try {

      $dataConfig = DB::table("payment_gateway")
        ->select('payment_gateway.*')
        ->where('payment_gateway.title', '=', "Stripe")
        ->first();
      if ($dataConfig == null) {
        $this->updateWebhookLog("Invalid Webhook Secret Key", null, null, null);

        return response()->json(['error' => 'Invalid Webhook Secret Key'], 400);
      }


      // Retrieve the Stripe secret for verifying the webhook signature
      $endpointSecret = $dataConfig->webhook_secret_key;
      //'whsec_iXNNVOAK4WDYBna79uPf11jdPiJSSD5r';

      // Retrieve the payload and Stripe-Signature header
      $payload = $request->getContent();
      $sigHeader = $request->header('Stripe-Signature');

      // Define the tolerance (default is 300 seconds)
      $tolerance = 300;

      // Extract the timestamp and the actual signature from the header
      list($timestamp, $signature) = $this->extractSignature($sigHeader);

      // Reconstruct the signed payload
      $signedPayload = "{$timestamp}.{$payload}";

      // Verify the signature by creating an HMAC with your webhook secret
      $expectedSignature = hash_hmac('sha256', $signedPayload, $endpointSecret);

      //  $paymentIntent = $event['data']['object'];
      //  $payloadJson= $paymentIntent['metadata'];

      // Decode the payload to get the event data
      $event = json_decode($payload, true);
      $paymentIntent = $event['data']['object'];
      $payloadJsonConvert = json_encode($paymentIntent);

      $timeStamp = date("Y-m-d H:i:s");
      $this->updateWebhookLog($request, $paymentIntent['id'] ?? "", $paymentIntent['status'], $payloadJsonConvert);

      // Compare the expected signature with the one Stripe sent
      if (hash_equals($expectedSignature, $signature) && (time() - $timestamp) < $tolerance) {
        // Signature is valid, process the event

        // Handle the event type (e.g., payment_intent.succeeded)
        switch ($event['type']) {
          case 'payment_intent.succeeded':

            $payLoadId = $paymentIntent['id'];
            $payload_notes = $paymentIntent['metadata'] ?? "";

            $preOrderId = $payload_notes['pre_order_id'] ?? "";
            $this->centrelizeData($preOrderId, $payLoadId);
            break;

          case 'payment_intent.payment_failed':
            $paymentIntent = $event['data']['object'];
            break;

          default:
            Log::info('Received unhandled event type: ' . $event['type']);
        }

        return response()->json(['status' => 'success'], 200);
      } else {
        // Invalid signature
        // Log::error('Invalid Stripe webhook signature');

        $this->updateWebhookLog("Invalid Stripe webhook signature", null, null, null);

        return response()->json(['error' => 'Invalid signature'], 400);
      }
    } catch (\Exception $e) {
      $this->updateWebhookLog($e, null, null, null);
      return response()->json(['error' => 'Invalid signature'], 400);
    }
  }

  /**
   * Extract timestamp and signature from Stripe-Signature header.
   */
  private function extractSignature($sigHeader)
  {
    // The Stripe-Signature header contains a timestamp and the signature
    // Format: t=<timestamp>,v1=<signature>
    $sigParts = explode(',', $sigHeader);
    $timestamp = substr($sigParts[0], 2);
    $signature = substr($sigParts[1], 3);

    return [$timestamp, $signature];
  }

  public function updateWebhookLog($response, $payment_id, $status, $payload)
  {
    $timeStamp = date("Y-m-d H:i:s");
    $webhookLogModel = new WebhookLogModel;
    $webhookLogModel->response = $response;
    $webhookLogModel->payment_id = $payment_id;
    $webhookLogModel->status = $status;
    $webhookLogModel->payload =  $payload;
    $webhookLogModel->created_at = $timeStamp;
    $webhookLogModel->updated_at = $timeStamp;
    $webhookLogModel->save();
  }
  public function handleWebhook(Request $request)

  {
    $dataConfig = DB::table("payment_gateway")
      ->select('payment_gateway.*')
      ->where('payment_gateway.title', '=', "Razorpay")
      ->first();
    if ($dataConfig == null) {
      $this->updateWebhookLog("Invalid Webhook Secret Key", null, null, null);
      return response()->json(['error' => 'Invalid Webhook Secret Key'], 400);
    }

    $secret = $dataConfig->webhook_secret_key;
    // Razorpay signature verification
    $webhookBody = $request->getContent();
    $webhookSignature = $request->header('X-Razorpay-Signature');

    if (!$this->verifySignature($webhookBody, $webhookSignature, $secret)) {

      return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
    }
    try {

      $event = $request->input('event');
      $payload = $request->input('payload.payment.entity');
      $payloadJson = json_encode($payload);

      $this->updateWebhookLog($request, $payload['id'] ?? "", $payload['status'] ?? "", $payloadJson);

      if ($event === 'payment.captured') {
        $payLoadId = $payload['id'];
        $payload_notes = $payload['notes']['payload'] ?? "";
        $payloadNotesEncoded = json_decode($payload_notes, true);
        $preOrderId = $payloadNotesEncoded['pre_order_id'] ?? "";
        $this->centrelizeData($preOrderId, $payLoadId);
      }

      return response()->json(['status' => 'ok'], 200);
    } catch (\Exception $e) {
      $this->updateWebhookLog($e, null, null, null);
      return response()->json([
        'status' => 'faild',
        'msg' => $e
      ], 500);
    }
  }

  private function verifySignature($body, $signature, $secret)
  {
    $generatedSignature = hash_hmac('sha256', $body, $secret);
    return hash_equals($generatedSignature, $signature);
  }

  function centrelizeData($preOrderId, $payLoadId)
  // function centrelizeData(Request $request)
  {
    $payload_id = $payLoadId;
    $payloadData = null;
    try {

      $preOrder = DB::table('pre_order')
        ->where('pre_order.id', $preOrderId)->first();
      if ($preOrder == null) {
        throw new \Exception("Pre order not found");
      }
      $payloadData = json_decode($preOrder->payload, true);
      if ($preOrder->type == "Wallet") {

        $payloadData['payment_transaction_id'] = $payload_id ?? null;
        request()->merge($payloadData);

        $transactionController = new AllTransactionController();
        $res = $transactionController->updateWalletMoneyData(request());
        $this->updteCentrelizeDataLog($res, $payload_id, $payloadData);
      }

      if ($preOrder->type == "Appointment") {


        $payloadData['payment_transaction_id'] = $payload_id ?? null;
        request()->merge($payloadData);

        $appointmentController = new AppointmentController();
        $res = $appointmentController->addData(request());
        $this->updteCentrelizeDataLog($res, $payload_id, $payloadData);
      }

      if ($preOrder->type == "Lab Appointment") {

        $payloadData['payment_transaction_id'] = $payload_id ?? null;
        request()->merge($payloadData);

        $appointmentController = new LabBookingController();
        $res = $appointmentController->addData(request());
        $this->updteCentrelizeDataLog($res, $payload_id, $payloadData);
      }
    } catch (\Exception $e) {
      $this->updteCentrelizeDataLog($e, $payload_id, $payloadData);
    }
  }
  function updatePayment(Request $request)
  // function centrelizeData(Request $request)
  {
  //      Log::info("Pre Order Updated 111111");
  
  //   $validator = Validator::make(request()->all(), [
  //     'payment_transaction_id' => 'required',
  //     'pre_order_id' => 'required'
  //   ]);
  //  Log::info("Pre Order Updated 22222");
  //   if ($validator->fails())
  //     return response(["response" => 400], 400);
 
    $payload_id = $request->payment_transaction_id;
    $preOrderId = $request->pre_order_id; 

    $payloadData = null;
     
    try {
         
      $preOrder = DB::table('pre_order')
        ->where('pre_order.id', $preOrderId)
         ->where('pre_order.payment_status', 'Pending')
        ->first();
      if ($preOrder == null) {
        throw new \Exception("Pre order not found");
      }
      $payloadData = json_decode($preOrder->payload, true);
      if ($preOrder->type == "Wallet") {

        $payloadData['payment_transaction_id'] = $payload_id ?? null;
        request()->merge($payloadData);

        $transactionController = new AllTransactionController();
        $res = $transactionController->updateWalletMoneyData(request());
        $this->updteCentrelizeDataLog($res, $payload_id, $payloadData);
      }

      if ($preOrder->type == "Appointment") {


        $payloadData['payment_transaction_id'] = $payload_id ?? null;
        request()->merge($payloadData);

        $appointmentController = new AppointmentController();
        $res = $appointmentController->addData(request());
        $this->updteCentrelizeDataLog($res, $payload_id, $payloadData);
      }

      if ($preOrder->type == "Lab Appointment") {

        $payloadData['payment_transaction_id'] = $payload_id ?? null;
        request()->merge($payloadData);

        $appointmentController = new LabBookingController();
        $res = $appointmentController->addData(request());
        $this->updteCentrelizeDataLog($res, $payload_id, $payloadData);
      }
    } catch (\Exception $e) {
    

      $this->updteCentrelizeDataLog($e, $payload_id, $payloadData);
        return Helpers::errorResponse("error $e");
    }
 

     $preOrder = PreOrderModel::where('id', $preOrderId)->first();
          $preOrder->payment_status = 'Success';
          $preOrder->updated_at = now();
          $preOrder->save();
   

    return Helpers::successResponse("success");
  }

  public function markPaymentFailed(Request $request)
{
    // $request->validate([
    //     'pre_order_id' => 'required',
    //     'reason' => 'nullable|string',
    // ]);
     
        $preOrderId = $request->pre_order_id; 

         $preOrder = PreOrderModel::where('id', $preOrderId)->first();
          
          if($preOrder != null){
            $preOrder->payment_status = 'Failed';
                $preOrder->failure_reason = $request->reason;
          $preOrder->updated_at = now();
          $preOrder->save();
          }
         

    return true;
}

  public function updteCentrelizeDataLog($response, $payment_id, $payload)
  {

    $timeStamp = date("Y-m-d H:i:s");
    $webhookCentrelizeDataLogmodel = new WebhookCentrelizeDataLogmodel;
    $webhookCentrelizeDataLogmodel->response = $response;
    $webhookCentrelizeDataLogmodel->payment_id = $payment_id;
    $webhookCentrelizeDataLogmodel->payload = $payload != null ? json_encode($payload) : null;
    $webhookCentrelizeDataLogmodel->created_at = $timeStamp;
    $webhookCentrelizeDataLogmodel->updated_at = $timeStamp;
    $webhookCentrelizeDataLogmodel->save();
  }
}
