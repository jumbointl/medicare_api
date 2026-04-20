<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TaxesModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TaxesController extends Controller
{
    //add new data
    function addData(Request $request){

    
        $validator = Validator::make(request()->all(), [
              'name' => 'required',
              'type' => 'required',
              'percent' => 'required|numeric|min:0|max:100'
      ]);
      if ($validator->fails())
      return response (["response"=>400],400);
     
      try{
            $alreadyAddedModel= TaxesModel::where("name",$request->name)->first();
            if($alreadyAddedModel)
            {
                return Helpers::errorResponse("Name already exists");
            
            }else{
                DB::beginTransaction();

                $timeStamp= date("Y-m-d H:i:s");
                $dataModel=new TaxesModel;

        

                $dataModel->name = $request->name ;

                $dataModel->type = $request->type ;

                $dataModel->percent = $request->percent ;

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
              
                    return Helpers::errorResponse("error $e");
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
        $dataModel= TaxesModel::where("id",$request->id)->first();
        if(isset($request->name)){
            $alreadyExists = TaxesModel::where('name', '=', $request->name)->where('id',"!=",$request->id)->first();
            if ($alreadyExists != null)
            {
                return Helpers::errorResponse("Name already exists");
            }
            else{
                $dataModel->name= $request->name;
            }
        }
        if(isset($request->type)){
            $dataModel->type= $request->type;
        }
        if(isset($request->percent)){
            $dataModel->percent= $request->percent;
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
        // Define the base query
        $query = DB::table("taxes")
            ->select(
                'taxes.*'

     
            )
            ->orderBy("taxes.created_at", "DESC");

        // Apply filters (like search)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('taxes.name', 'like', '%' . $search . '%')
                    ->orWhere('taxes.type', 'like', '%' . $search . '%')
                    ->orWhere('taxes.percent', 'LIKE', "%$search%");
                 
            });
        }

 
 


        // Calculate total records first (without pagination)
        $total_record = $query->count(); // Count the total records before pagination

        // Apply pagination if start and end are provided
        if ($request->filled(['start', 'end'])) {
            $start = $request->start;
            $end = $request->end;
            $query->skip($start)->take($end - $start);
        }

        // Get the paginated data
        $data = $query->get();

        // Return the response with total records and data
        $response = [
            "response" => 200,
            "total_record" => $total_record,
            'data' => $data,
        ];

        return response()->json($response, 200);
    }


        // get data by id
    function getDataById($id)
    {

      $data = DB::table("taxes")
      ->select(   'taxes.*'
         
      )
    
      ->where('taxes.id','=',$id)
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
                    $dataModel= TaxesModel::where("id",$request->id)->first();
                    $qResponce= $dataModel->delete();
                    if($qResponce){
                     
                   
                    return Helpers::successResponse("successfully Deleted");}
                    else 
                    return Helpers::errorResponse("error");    
             
            }
        
         catch(\Exception $e){
                  
            return Helpers::errorResponse("This record cannot be deleted because it is linked to multiple data entries in the system. You can only deactivate it to prevent future use.");
                      }         
        
        }

}
