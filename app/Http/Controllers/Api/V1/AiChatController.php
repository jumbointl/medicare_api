<?php

namespace App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\OpenAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiChatController extends Controller
{
    public function chat(Request $request, OpenAiService $ai)
    {
        $request->validate([
            'message'    => 'required|string',
            'city_id'    => 'required|integer',
            'session_id' => 'nullable|string',
        ]);

        /* ===============================
         | 1️⃣ Create or Validate Session
         =============================== */
        if (
            $request->session_id &&
            DB::table('chat_sessions')->where('id', $request->session_id)->exists()
        ) {
            $sessionId = $request->session_id;
        } else {
            $sessionId = (string) Str::uuid();
            DB::table('chat_sessions')->insert([
                'id'      => $sessionId,
                'city_id'=> $request->city_id,
                'user_id'=> $request->user_id ?? null,
                'created_at' => now()
            ]);
        }

        /* ===============================
         | 2️⃣ Save User Message
         =============================== */
        DB::table('chat_messages')->insert([
            'session_id'   => $sessionId,
            'sender'       => 'user',
            'message_type' => 'text',
            'message_text' => $request->message,
            'created_at'   => now()
        ]);

        /* ===============================
         | 3️⃣ Ask AI (SESSION AWARE)
         =============================== */
        $aiResult = $ai->analyzeSymptoms(
            $request->message,
            $sessionId
        );

    
$reply       = $aiResult['reply'] ?? 'Please consult a doctor.';
$urgency     = $aiResult['urgency'] ?? 'low';
$filters     = $aiResult['filters'] ?? [];

Log::info('AI Filters', ['filters' => $filters]);

/* ===============================
 | 4️⃣ Doctor Recommendation
 =============================== */
$doctors = collect();

if($aiResult['show_doctors']==true)
    {
     $query = DB::table('doctors')
    ->select(
        'doctors.id',
        'doctors.user_id',
        'doctors.specialization',
        'doctors.ex_year',
        'users.f_name',
        'users.l_name',
        'users.image',
        'users.gender',
        'department.title as department_name',
        'clinics.title as clinic_title',
        'clinics.address as clinic_address',
        'cities.title as city_title',
        'states.title as state_title',
        DB::raw('IFNULL(AVG(dr.points),0) as average_rating'),
        DB::raw('COUNT(DISTINCT dr.id) as review_count'),
        DB::raw('COUNT(DISTINCT ap.id) as total_appointment_done')
    )
    ->join('users', 'users.id', '=', 'doctors.user_id')
    ->join('department', 'department.id', '=', 'doctors.department')
    ->join('clinics', 'clinics.id', '=', 'doctors.clinic_id')
    ->join('cities', 'cities.id', '=', 'clinics.city_id')
    ->join('states', 'states.id', '=', 'cities.state_id')
    ->leftJoin('doctors_review as dr', 'dr.doctor_id', '=', 'doctors.user_id')
    ->leftJoin('appointments as ap', 'ap.doct_id', '=', 'doctors.user_id')
    ->where('cities.id', $request->city_id)
    ->where('doctors.active', 1);

    if (!empty($filters['department'])) {
    $query->where('department.title', 'LIKE', '%' . $filters['department'] . '%');
}


/* 🔹 Doctor name filter */
if (!empty($filters['doctor_name'])) {
    $name = trim($filters['doctor_name']);

    $query->where(function ($q) use ($name) {
        // Match "First Last"
        $q->where(DB::raw("CONCAT(users.f_name, ' ', users.l_name)"), 'LIKE', "%{$name}%")
          // Match "Last First"
          ->orWhere(DB::raw("CONCAT(users.l_name, ' ', users.f_name)"), 'LIKE', "%{$name}%")
          // Fallback: match either name part
          ->orWhere('users.f_name', 'LIKE', "%{$name}%")
          ->orWhere('users.l_name', 'LIKE', "%{$name}%");
    });
}

/* 🔹 Clinic filter */
if (!empty($filters['clinic_title'])) {
    $query->where('clinics.title', 'LIKE', '%' . $filters['clinic_title'] . '%');
}

/* 🔹 Gender filter */
if (!empty($filters['gender'])) {
    $query->where('users.gender', $filters['gender']);
}

/* 🔹 Experience filter */
if (!empty($filters['min_experience'])) {
    $query->where('doctors.ex_year', '>=', (int) $filters['min_experience']);
}

/* 🔹 GROUP BY (mandatory for HAVING) */
$query->groupBy(
    'doctors.id',
    'doctors.user_id',
    'doctors.specialization',
    'doctors.ex_year',
    'users.f_name',
    'users.l_name',
    'users.image',
    'users.gender',
    'department.title',
    'clinics.title',
    'clinics.address',
    'cities.title',
    'states.title'
);

/* 🔹 Rating / Reviews / Appointments (HAVING) */
if (!empty($filters['min_rating'])) {
    $query->havingRaw('AVG(dr.points) >= ?', [(float) $filters['min_rating']]);
}

if (!empty($filters['min_reviews'])) {
    $query->havingRaw('COUNT(DISTINCT dr.id) >= ?', [(int) $filters['min_reviews']]);
}

if (!empty($filters['min_appointments'])) {
    $query->havingRaw('COUNT(DISTINCT ap.id) >= ?', [(int) $filters['min_appointments']]);
}

/* 🔹 Ranking + Fetch */
$doctors = $query
    ->orderByDesc('average_rating')
    ->orderByDesc('review_count')
    ->orderByDesc('total_appointment_done')
    ->limit(5)
    ->get();   
    
}
else{
    $doctors=[];
}



        /* ===============================
         | 5️⃣ Save AI Message
         =============================== */
        DB::table('chat_messages')->insert([
            'session_id'   => $sessionId,
            'sender'       => 'ai',
            'message_type' => 'text',
            'message_text' => $reply,
            'created_at'   => now()
        ]);

        /* ===============================
         | 6️⃣ API Response
         =============================== */
        return response()->json([
            'response'   => 200,
            'session_id' => $sessionId,
            'reply'      => $reply,
            'urgency'    => $urgency,
            'doctors'    => $doctors,
            'show_doctors' => $aiResult['show_doctors']??false
        ]);
    }
}
