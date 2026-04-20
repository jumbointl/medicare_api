<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BlogPostModel;
use App\Models\BlogAuthorModel;
use Illuminate\Support\Facades\Validator;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPostController extends Controller
{
    //add new data
    function addData(Request $request)
    {


        $validator = Validator::make(request()->all(), [
            'title' => 'required|string|max:255',
            'cat_id' => 'required',
            'user_id'  => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);

        try {
            $alreadyAddedModel = BlogPostModel::where("title", $request->title)->first();
            if ($alreadyAddedModel) {
                return Helpers::errorResponse("title already exists");
            } else {
                DB::beginTransaction();
                $clinicId = null;

                $userData = DB::table("users")
                    ->select(
                        'users.*',
                    )
                    ->where('users.id', '=', $request->user_id)
                    ->first();

                if ($userData) {
                    $clinicId = $userData->clinic_id ?? null;
                }

                if ($clinicId == null) {
                    $userDataRoleAss = DB::table("doctors")
                        ->select(
                            'doctors.*'
                        )
                        ->where('doctors.user_id', '=', $request->user_id)
                        ->first();
                    if ($userDataRoleAss) {
                        $clinicId = $userDataRoleAss->clinic_id ?? null;
                    }
                }

                $timeStamp = date("Y-m-d H:i:s");
                $dataModel = new BlogPostModel;

                $dataModel->title = $request->title;
                $dataModel->metatag = $request->metatag ?? Str::slug($request->title);
                $dataModel->cat_id = $request->cat_id;
                $dataModel->clinic_id = $clinicId ?? null;



                $dataModel->created_at = $timeStamp;
                $dataModel->updated_at = $timeStamp;
                // if(isset($request->image)){

                //       $dataModel->image =  $request->hasFile('image') ? Helpers::uploadImage('department/', $request->file('image')) : null;
                // }


                $qResponce = $dataModel->save();
                if ($qResponce) {
                    DB::commit();
                    if ($request->user_id) {
                        $dataModelBA = new BlogAuthorModel;
                        $dataModelBA->user_id = $request->user_id;
                        $dataModelBA->blog_id = $dataModel->id;
                        $dataModelBA->notes = "Author";
                        $dataModelBA->role = "Author";
                        $dataModelBA->save();
                    }
                    return Helpers::successWithIdResponse("successfully", $dataModel->id);
                } else {
                    DB::rollBack();

                    return Helpers::errorResponse("error");
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return Helpers::errorResponse("error $e");
        }
    }
    // Update Deapartment
    function updateData(Request $request)
    {

        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            DB::beginTransaction();
            $dataModel = BlogPostModel::where("id", $request->id)->first();
            if (isset($request->title)) {
                $alreadyExists = BlogPostModel::where('title', '=', $request->title)->where('id', "!=", $request->id)->first();
                if ($alreadyExists != null) {
                    return Helpers::errorResponse("title already exists");
                } else {
                    $dataModel->title = $request->title;
                    $dataModel->metatag = $request->metatag ?? Str::slug($request->title);
                }
            }

            if (isset($request->keywords)) {
                $dataModel->keywords = $request->keywords;
            }
            if (isset($request->description)) {
                $dataModel->description = $request->description;
            }
            if (isset($request->content)) {
                $dataModel->content = $request->content;
            }
            if (isset($request->status)) {
                $dataModel->status = $request->status;
            }

            if (isset($request->cat_id)) {
                $dataModel->cat_id = $request->cat_id;
            }
            if (isset($request->featured)) {
                $dataModel->featured = $request->featured;
            }
            if (isset($request->preferences)) {
                $dataModel->preferences = $request->preferences;
            }





            if (isset($request->image)) {
                if ($request->hasFile('image')) {

                    $oldImage = $dataModel->image;
                    $dataModel->image =  Helpers::uploadImage('blog/', $request->file('image'));
                    if (isset($oldImage)) {
                        if ($oldImage != "def.png") {
                            Helpers::deleteImage($oldImage);
                        }
                    }
                }
            }

            $timeStamp = date("Y-m-d H:i:s");
            $dataModel->updated_at = $timeStamp;
            $qResponce = $dataModel->save();
            if ($qResponce) {
                DB::commit();
                return Helpers::successResponse("successfully");
            } else {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error $e");
        }
    }

    function updateDataToPublish(Request $request)
    {

        $validator = Validator::make(request()->all(), [
            'id' => 'required',
            'user_id' => 'required',
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            DB::beginTransaction();
            $dataModel = BlogPostModel::where("id", $request->id)->first();

            $dataModel->status = "Published";
            $timeStamp = date("Y-m-d H:i:s");
            $dataModel->updated_at = $timeStamp;
            $qResponce = $dataModel->save();
            if ($qResponce) {
                $dataModelBA = BlogAuthorModel::where("blog_id", $request->id)->where("user_id", $request->user_id)->first();

                if (!$dataModelBA) {
                    $notes = "Published By";

                    $dataModelBM = new BlogAuthorModel;

                    $dataModelBM->user_id = $request->user_id;
                    $dataModelBM->blog_id = $request->id;
                    $dataModelBM->notes = $notes;
                    //   $dataModelBM->publisher = 1 ;
                    $dataModelBM->role = "Editor";

                    $dataModelBM->created_at = $timeStamp;
                    $dataModelBM->updated_at = $timeStamp;
                    $qResponce = $dataModelBM->save();
                }
                DB::commit();
                return Helpers::successResponse("successfully");
            } else {
                DB::rollBack();
                return Helpers::errorResponse("error");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return Helpers::errorResponse("error $e");
        }
    }


    // Remove Image
    function removeImage(Request $request)
    {


        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            $dataModel = BlogPostModel::where("id", $request->id)->first();


            $oldImage = $dataModel->image;
            if (isset($oldImage)) {
                if ($oldImage != "def.png") {
                    Helpers::deleteImage($oldImage);
                }

                $dataModel->image = null;
            }

            $timeStamp = date("Y-m-d H:i:s");
            $dataModel->updated_at = $timeStamp;

            $qResponce = $dataModel->save();
            if ($qResponce)
                return Helpers::successResponse("successfully");

            else
                return Helpers::errorResponse("error");
        } catch (\Exception $e) {

            return Helpers::errorResponse("error");
        }
    }



    public function getData(Request $request)
    {
        $query = DB::table("blog_post")
            ->select(
                'blog_post.*',
                'blog_post_cat.title as cat_title'
            )
            ->join('blog_post_cat', 'blog_post_cat.id', '=', 'blog_post.cat_id')
            ->orderBy("blog_post.preferences", "ASC");

        // 🔍 Filter by search
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('blog_post.title', 'like', '%' . $search . '%');
            });
        }

        // 🔍 Filter by status
        if ($request->filled('status')) {
            $query->where('blog_post.status', $request->status);
        }

        // 🔍 Filter by category
        if ($request->filled('cat_id')) {
            $query->where('blog_post.cat_id', $request->cat_id);
        }

        if ($request->filled('clinic_id')) {
            $query->where('blog_post.clinic_id', $request->clinic_id);
        }

        if ($request->filled('featured')) {
            $query->where('blog_post.featured', $request->featured);
        }

        // ✅ Filter by user_id (author/editor/publisher)
        if ($request->filled('user_id')) {
            $userId = $request->user_id;
            $query->whereExists(function ($subQuery) use ($userId) {
                $subQuery->select(DB::raw(1))
                    ->from('blog_author')
                    ->whereColumn('blog_author.blog_id', 'blog_post.id')
                    ->where('blog_author.user_id', $userId);
            });
        }

        // Get total count before pagination
        $total_record = $query->count();

        // Pagination
        if ($request->filled(['start', 'end'])) {
            $start = $request->start;
            $end = $request->end;
            $query->skip($start)->take($end - $start);
        }

        $data = $query->get();

        // Attach author info
        if ($data && count($data) > 0) {
            foreach ($data as $item) {
                $item->author = DB::table("blog_author")
                    ->select(
                        'blog_author.*',
                        'users.f_name',
                        'users.l_name',
                        'users.image',
                        'doctors.specialization'
                    )
                    ->join('users', 'users.id', '=', 'blog_author.user_id')
                    ->leftJoin('doctors', 'doctors.user_id', '=', 'blog_author.user_id')
                    ->where('blog_author.blog_id', '=', $item->id)
                    ->get();
            }
        }

        return response()->json([
            "response" => 200,
            "total_record" => $total_record,
            'data' => $data,
        ], 200);
    }

    // get data by id

    function getDataById($id)
    {

        $data = DB::table("blog_post")
            ->select(
                'blog_post.*',
                'blog_post_cat.title as cat_title'
            )
            ->join('blog_post_cat', 'blog_post_cat.id', '=', 'blog_post.cat_id')
            ->where('blog_post.id', '=', $id)
            ->first();
        if ($data) {
            DB::table('blog_post')
                ->where('id', $id)
                ->increment('views', 1);
            $data->author = DB::table("blog_author")
                ->select(
                    'blog_author.*',
                    "users.f_name",
                    "users.l_name",
                    "users.image",
                    'doctors.specialization'

                )
                ->join('users', 'users.id', '=', 'blog_author.user_id')
                ->leftJoin('doctors', 'doctors.user_id', '=', 'blog_author.user_id')
                ->where('blog_author.blog_id', '=', $data->id)
                ->get();
        }

        $response = [
            "response" => 200,
            'data' => $data,
        ];

        return response($response, 200);
    }

    function deleteData(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'id' => 'required'
        ]);
        if ($validator->fails())
            return response(["response" => 400], 400);
        try {
            $dataModel = BlogPostModel::where("id", $request->id)->first();
            $oldImage = $dataModel->image;
            $qResponce = $dataModel->delete();
            if ($qResponce) {

                if (isset($oldImage)) {
                    if ($oldImage != "def.png") {
                        Helpers::deleteImage($oldImage);
                    }
                }
                return Helpers::successResponse("successfully Deleted");
            } else
                return Helpers::errorResponse("error");
        } catch (\Exception $e) {

            return Helpers::errorResponse("This record cannot be deleted because it is linked to multiple data entries in the system. You can only deactivate it to prevent future use.");
        }
    }
}
