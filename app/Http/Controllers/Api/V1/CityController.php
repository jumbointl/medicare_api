<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CityModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CityController extends Controller
{
    //add new data
    function addData(Request $request){

    
        $validator = Validator::make(request()->all(), [
            'title' => 'required',
             'state_id' => 'required'
      ]);
 
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
            $alreadyAddedModel= CityModel::where("title",$request->title)->where("state_id",$request->state_id)->first();
            if($alreadyAddedModel)
            {
                return Helpers::errorResponse("title already exists");
            
            }else{
                DB::beginTransaction();

                $timeStamp= date("Y-m-d H:i:s");
                $dataModel=new CityModel;
                
                $dataModel->title = $request->title ;
                $dataModel->state_id  = $request->state_id;
            
                $dataModel->created_at=$timeStamp;
                $dataModel->updated_at=$timeStamp;
          
         
                $qResponce = $dataModel->save();
                if($qResponce)
               {
                DB::commit();
                
                return Helpers::successWithIdResponse("successfully",$dataModel->id);}
            
                else 
                {   DB::rollBack();
                    
                    return Helpers::errorResponse("error");}
            }
           
        }

     catch(\Exception $e){
             DB::rollBack();
              
                    return Helpers::errorResponse("error");
                  }
}
// Update Deapartment
function updateData(Request $request){
    
    $validator = Validator::make(request()->all(), [
        'id' => 'required'
  ]);
  if ($validator->fails())
  return response (["response"=>400],400);
    try{
        DB::beginTransaction();
        $dataModel= CityModel::where("id",$request->id)->first();
        if(isset($request->title)){
            $alreadyExists = CityModel::where('title', '=', $request->title)->where('id',"!=",$request->id)->where("state_id",$request->state_id)->first();
            if ($alreadyExists != null)
            {
                return Helpers::errorResponse("title already exists");
            }
            else{
                $dataModel->title= $request->title;
            }
        }
        if(isset($request->state_id)){
            $dataModel->state_id= $request->state_id;
        }
        if(isset($request->active)){
            $dataModel->active= $request->active;
        }

        if(isset($request->latitude)){
            $dataModel->latitude= $request->latitude;
        }
        if(isset($request->longitude)){
            $dataModel->longitude= $request->longitude;
        }
        if(isset($request->default_city)){
            if($request->default_city=="1"){
                CityModel::where('default_city', 1)->update(['default_city' => 0]);
                $dataModel->default_city = 1;

            }
            else{
                $dataModel->default_city= $request->default_city;
            }
           
        }

        $timeStamp= date("Y-m-d H:i:s");
        $dataModel->updated_at=$timeStamp;
                $qResponce= $dataModel->save();
                if($qResponce)
                {
                   
            

                    DB::commit();
                    return Helpers::successResponse("successfully");}
    
                else 
                {
                    DB::rollBack();
                    return Helpers::errorResponse("error");}
    }
    

 catch(\Exception $e){
    DB::rollBack();
                return Helpers::errorResponse("error");
              }
            }




               // get data

public function getData(Request $request)
    {

      $query = DB::table("cities")
      ->select('cities.*',
      'states.title as state_title'
      )
        ->join('states', 'states.id', '=', 'cities.state_id');
      

   // Apply the 'active' filter if it's passed in the request

    if (!empty($request->active)) {
    $query->where('cities.active', '=', $request->active);
  }
    

        if (!empty($request->search)) {
        $search = $request->input('search');
        $query->where(function ($q) use ($search) {
            $q->where('cities.title', 'like', '%' . $search . '%')
                ->orWhere('states.title', 'like', '%' . $search . '%');
        });
    }


    $data = $query->OrderBy('cities.created_at', 'desc')->get();
    

            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }

        function getDataActive()
        {
    
          $data = DB::table("cities")
          ->select('cities.*')
          ->where('active',1)
            ->get();
          
                $response = [
                    "response"=>200,
                    'data'=>$data,
                ];
            
          return response($response, 200);
            }
    

        

           // get data by id

    function getDataById($id)
    {

        $data = DB::table("cities")
      ->select('cities.*',
      'states.title as state_title'
      )
        ->join('states', 'states.id', '=', 'cities.state_id')
      ->where('cities.id','=',$id)
        ->first();
      
            $response = [
                "response"=>200,
                'data'=>$data,
            ];
        
      return response($response, 200);
        }

        function deleteData(Request $request){
            $validator = Validator::make(request()->all(), [
                'id' => 'required'
          ]);
          if ($validator->fails())
          return response (["response"=>400],400);
            try{ 
                    $dataModel= CityModel::where("id",$request->id)->first();
               
                    $qResponce= $dataModel->delete();
                    if($qResponce){
                     

                    return Helpers::successResponse("successfully Deleted");}
                    else 
                    return Helpers::errorResponse("error");    
             
            }
        
         catch(\Exception $e){
                  
                        return Helpers::errorResponse("Cannot be deleted. You can only deactivate.");
                      }         
        
        }

        public function getCityByLatLng(Request $request)
{
//     $validator = Validator::make(request()->all(), [
//         'latitude' => 'required',
//          'longitude' => 'required'
//   ]);

//   if ($validator->fails())
//   return response (["response"=>400],400);

    $latitude = $request->input('latitude');
    $longitude = $request->input('longitude');



    // Use a geocoding service (e.g., Google Maps API) to get city and state

    $defaultCity = DB::table("cities")
        ->select('cities.*',
        'states.title as state_title'
        )

            ->join('states','states.id','=','cities.state_id')
            ->where('cities.default_city', true)
            ->first();
            if($defaultCity){
                $data= [  
                    'city' => $defaultCity->title, 
                    'city_id' => $defaultCity->id];
            }else{
                $defaultCity = DB::table("cities")
        ->select('cities.*',
        'states.title as state_title'
        )

            ->join('states','states.id','=','cities.state_id')
            ->first();

            if($defaultCity){
                $data= [  
                    'city' => $defaultCity->title, 
                    'city_id' => $defaultCity->id];
            }
            }
      
        if($latitude!=null&&$longitude!=null){
    
            $location = $this->getCityStateFromCoordinates($latitude, $longitude);

            if ($location) {
                
                $city = $location['city'];
                $state = $location['state'];
        
                $matchedLocation = DB::table("cities")
                ->select('cities.*',
                'states.title as state_title')
                    ->join('states','states.id','=','cities.state_id')
                    ->where('cities.active', 1)
                    ->where('cities.title', $city)
                    ->where('states.title', $state)
                    ->first();
        
                if ($matchedLocation) {
                    $data= [  
                    'city' => $matchedLocation->title, 
                    'city_id' => $matchedLocation->id];
        
                }
        
            }
    
            }
        

    $response = [
        "response"=>200,
        'data'=>$data,
    ];
    // Return default city and state
    return response()->json($response);
}

private function getCityStateFromCoordinates($latitude, $longitude)

{  

      $apiKey = ''; // Replace with your actual Google Maps API Key

    $configurations = DB::table("configurations")
                ->select('configurations.*')
                
                    ->where('configurations.id_name', 'google_map_api_key')
                    ->first();

                    if(!empty($configurations->value)){

                        $apiKey=$configurations->value;
                    }

                    if(empty($apiKey))
                    {
                        return null;
                    }

    $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$latitude},{$longitude}&key={$apiKey}";

    // Make an HTTP request to the Google Maps API
    $response = file_get_contents($url);

    // Decode the JSON response
    $responseData = json_decode($response, true);


    if ($responseData['status'] === 'OK') {
        $results = $responseData['results'];

        foreach ($results as $result) {
      
            foreach ($result['address_components'] as $component) {
                if (in_array('locality', $component['types'])) {
                    $city = $component['long_name'];
                }

                if (in_array('administrative_area_level_1', $component['types'])) {
                    $state = $component['long_name'];
                }
            }

            if (isset($city) && isset($state)) {
                return ['city' => $city, 'state' => $state];
            }
        }
    }

    // Return null if no city or state is found
    return null;
}


}
