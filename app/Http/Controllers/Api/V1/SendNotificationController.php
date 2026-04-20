<?php


namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\CentralLogics\Helpers;
use App\Models\WebAppSettingsModel;
use GuzzleHttp\Client;
use Google\Client as GoogleClient;
class SendNotificationController extends Controller
{

  public function subscribeToTopic(Request $request)
  {
      // Fetch Google Service Account JSON
      $data = DB::table("configurations")
          ->select('configurations.*')
          ->where('configurations.id_name', '=', 'google_service_account_json')
          ->first();
  
      if ($data == null || $data->value == null) {
          return Helpers::errorResponse("Google service account JSON not found or empty");
      }
  
      $GOOGLE_SERVICE_ACCOUNT_JSON = $data->value;
      $client = new GoogleClient();
  
      // Decode the JSON and authenticate
      $serviceAccountJson = json_decode($GOOGLE_SERVICE_ACCOUNT_JSON, true);
      $client->setAuthConfig($serviceAccountJson);
      $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
      $client->fetchAccessTokenWithAssertion();
      $accessTokenRes = $client->getAccessToken();
      $accessToken = $accessTokenRes['access_token'];
  
      if (!isset($accessToken)) {
          return Helpers::errorResponse("Failed to get access token");
      }
  
      // Get the FCM token and topic from the request
      $token = $request->input('token'); // FCM device token
      $topic = $request->input('topic'); // The topic to subscribe to
  
      if (empty($token) || empty($topic)) {
          return [
              "response" => 400, // Bad Request
              "status" => false,
              "message" => "Token or topic cannot be empty",
          ];
      }
  
      // Construct the subscription URL
      $url = 'https://fcm.googleapis.com/v1/projects/medical-care-a94cb:subscribeToTopic';
  
      // Prepare the send data payload with the correct structure
      $sendData = [
          "registrationTokens" => [$token], // Pass the token here
          "topic" => $topic, // The topic to subscribe to
      ];
  
      // Send the HTTP request to subscribe the token to the topic
      $sendResponse = Http::withHeaders([
          'Authorization' => 'Bearer ' . $accessToken,
          'Content-Type' => 'application/json',
      ])->post($url, $sendData);
  
      // Handle response and log any errors
      if ($sendResponse->successful()) {
          return [
              "response" => $sendResponse->status(),
              "status" => true,
              "message" => "Successfully subscribed to topic",
          ];
      } else {
          \Log::error("FCM Subscribe Error: ", [
              'status' => $sendResponse->status(),
              'body' => $sendResponse->body(),
          ]);
          return [
              "response" => $sendResponse->status(),
              "status" => false,
              "message" => $sendResponse->body(), // Return actual Firebase response
          ];
      }
  }
  
  
  
  
  function sendFirebaseNotificationToToken($title,$body,$imageUrl,$token){
    $data = DB::table("configurations")
    ->select('configurations.*')
    ->where('configurations.id_name','=','google_service_account_json')
      ->first();
      if($data==null){
        return Helpers::errorResponse("error");
      }
      if($data->value==null){
        return Helpers::errorResponse("error");
      }
      $GOOGLE_SERVICE_ACCOUNT_JSON=$data->value;
    
    $client = new GoogleClient();
  
  
    // Decode the JSON string from the environment variable
    $serviceAccountJson = json_decode($GOOGLE_SERVICE_ACCOUNT_JSON, true);
  
    // Authenticate using the decoded JSON
    $client->setAuthConfig($serviceAccountJson);
  
    // Set the required scopes
    $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
  
    // Authenticate and get the token
    $client->fetchAccessTokenWithAssertion();
  
    $accessTokenRes = $client->getAccessToken();
    
   $accessToken= $accessTokenRes['access_token'];
   if(!isset($accessToken)){
    return Helpers::errorResponse("error");
   }

      $sendData = [
        "message" => [
            "token" => $token,
            "notification" => [
                "title" => $title,
                "body" => $body,
                "image" => $imageUrl != null && $imageUrl != "" ? $imageUrl : "",
            ],
            "data" => [
                "sound" => "default",
                "content_available" => "true",
                "priority" => "high",
                "imageUrl" => $imageUrl != null && $imageUrl != "" ? $imageUrl : "",
                "title" => $title,
                "body" => $body,
            ]
        ]
    ];
    $sendResponse = Http::withHeaders([
      'Authorization' => 'Bearer '.$accessToken 
    ])->post('https://fcm.googleapis.com/v1/projects/medical-care-a94cb/messages:send', $sendData);
  
    $response = [
        "response"=>$sendResponse->status(),
        "message"=>$sendResponse->body(),
    ];

 return $response;
      
}
    function sendFirebaseNotificationToTopic($title,$body,$imageUrl,$topic){
      $data = DB::table("configurations")
      ->select('configurations.*')
      ->where('configurations.id_name','=','google_service_account_json')
        ->first();
        if($data==null){
          return Helpers::errorResponse("error");
        }
        if($data->value==null){
          return Helpers::errorResponse("error");
        }
        $GOOGLE_SERVICE_ACCOUNT_JSON=$data->value;
      
      $client = new GoogleClient();
    
    
      // Decode the JSON string from the environment variable
      $serviceAccountJson = json_decode($GOOGLE_SERVICE_ACCOUNT_JSON, true);
    
      // Authenticate using the decoded JSON
      $client->setAuthConfig($serviceAccountJson);
    
      // Set the required scopes
      $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
    
      // Authenticate and get the token
      $client->fetchAccessTokenWithAssertion();
    
      $accessTokenRes = $client->getAccessToken();
      
     $accessToken= $accessTokenRes['access_token'];
     if(!isset($accessToken)){
      return Helpers::errorResponse("error");
     }
  
        $sendData = [
          "message" => [
              "topic" => $topic,
              "notification" => [
                  "title" => $title,
                  "body" => $body,
                  "image" => $imageUrl != null && $imageUrl != "" ? $imageUrl : "",
              ],
              "data" => [
                  "sound" => "default",
                  "content_available" => "true",
                  "priority" => "high",
                  "imageUrl" => $imageUrl != null && $imageUrl != "" ? $imageUrl : "",
                  "title" => $title,
                  "body" => $body,
              ]
          ]
      ];
       
        $sendResponse = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken 
        ])->post('https://fcm.googleapis.com/v1/projects/medical-care-a94cb/messages:send', $sendData);
      
        $response = [
            "response"=>$sendResponse->status(),
            "message"=>$sendResponse->body(),
        ];
    
     return $response;
        

    }
    
   function sendReqFirebaseNotificationToTopic(Request $request)
   {
    $validator = Validator::make(request()->all(), [
      'title' => 'required',
      'body'=>'required',
      "topic"=>'required'
]);
    
if ($validator->fails()){
  return response (["response"=>400],400);
}

    
 $sendResponse = $this-> sendFirebaseNotificationToTopic($request->title,$request->body,$request->image_url,$request->topic);
  
 return response($sendResponse, 200);
    
}
function sendReqFirebaseNotificationToToken(Request $request)
{

  $validator = Validator::make(request()->all(), [
    'title' => 'required',
    'body'=>'required',
    "token"=>'required'
]);
  
if ($validator->fails()){
return response (["response"=>400],400);
}
 

    $sendResponse = $this-> sendFirebaseNotificationToToken($request->title,$request->body,$request->image_url,$request->token);
 return response($sendResponse, 200);
 
}
  }