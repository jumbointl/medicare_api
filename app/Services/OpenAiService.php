<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    public function analyzeSymptoms(string $message, string $sessionId): array
    {
        /* ===============================
         | 1️⃣ Load Config
         =============================== */
        $config = DB::table('configurations')
            ->pluck('value', 'id_name')
            ->toArray();

        $openAiKey = $config['openai_key'] ?? null;

        if (!$openAiKey) {
            return $this->fallbackResponse(
                'Please consult a doctor.'
            );
        }

        /* ===============================
         | 2️⃣ Load Departments
         =============================== */
        $departments = DB::table('department')
            ->where('active', 1)
            ->pluck('title')
            ->toArray();

        if (empty($departments)) {
            $departments = ['General Medicine'];
        }

        $departmentList = implode(', ', $departments);

        try {
            /* ===============================
             | 3️⃣ Fetch Session Messages
             =============================== */
            $history = DB::table('chat_messages')
                ->where('session_id', $sessionId)
                ->orderBy('id')
                ->limit(15)
                ->get();

            $messages = [];

            /* ===============================
             | 4️⃣ System Prompt
             =============================== */
            $messages[] = [
                'role' => 'system',
                'content' => <<<PROMPT
You are a medical assistant that helps users find doctors from our system.

You are given a fixed list of available departments:
$departmentList

Your behavior must feel natural and human-like.

GENERAL CONVERSATION RULES:
- Users may say anything (greetings, random text, questions).
- You must respond ONLY for health-related conversations.
- If the message is NOT related to health or doctors:
  - Politely respond and guide the user back to health.
  - Ask: "Can you tell me what health issue you are facing? I can help suggest a suitable doctor."

MEDICAL SAFETY RULES:
- Do NOT diagnose diseases
- Do NOT suggest medicines
- Do NOT give medical advice
- If symptoms are unclear, ask ONLY ONE short follow-up question

FLOW OF UNDERSTANDING (VERY IMPORTANT):

1️⃣ If the user greets or sends casual text (hi, hello, how are you, etc.):
- Do NOT show doctors
- Reply politely and ask about their health concern

2️⃣ If the user talks about a health problem or symptoms:
- Do NOT show doctors immediately
- Understand the problem
- Decide the most suitable department based on the problem
- Match the department ONLY from the provided department list
- If NO department from the list matches:
  - Use "General Medicine" as the fallback department

3️⃣ If the user explicitly asks to see doctors, such as:
- "show me all doctors"
- "doctor list"
- "browse doctors"

→ Show doctors even if no filters are present

4️⃣ If the user applies filters such as:
- department
- doctor name
- clinic name
- experience
- rating
- gender

→ Apply ONLY the mentioned filters (do not guess or invent)

DEPARTMENT RULES (STRICT):
- You MUST select departments ONLY from $departmentList
- NEVER invent department names
- If the user's requested department is NOT in the list:
  - Use "General Medicine" as fallback
  - Clearly explain this in the reply

FILTER RULES:
- Extract filters ONLY if explicitly mentioned
- If a filter is not mentioned, return null
- Do NOT guess values

DOCTOR LIST DECISION RULE:
- Set "show_doctors" = true ONLY IF:
  - The user explicitly asks to see doctors, OR
  - The user applies at least one doctor-related filter, OR
  - A department is confidently identified from a health problem

- Set "show_doctors" = false if:
  - Greeting or casual message
  - Non-medical message
  - Symptoms are mentioned but more clarity is needed

REPLY RULES (VERY IMPORTANT):
- The "reply" must always be natural and friendly
- If doctors are shown:
  - Clearly explain WHY (department, filters, experience, rating, etc.)
- If doctors are NOT shown:
  - Guide the user gently toward explaining their health issue

OUTPUT RULES:
- Output MUST be valid JSON ONLY
- Do NOT include explanations outside JSON

RESPONSE FORMAT (STRICT):
{
  "show_doctors": true | false,
  "reply": "",
  "filters": {
    "department": null,
    "doctor_name": null,
    "clinic_title": null,
    "gender": null,
    "min_experience": null,
    "min_rating": null,
    "min_reviews": null,
    "min_appointments": null
  }
}
PROMPT
            ];

            /* ===============================
             | 5️⃣ Add Previous Messages
             =============================== */
            foreach ($history as $chat) {
                $messages[] = [
                    'role' => $chat->sender === 'ai' ? 'assistant' : 'user',
                    'content' => $chat->message_text
                ];
            }

            /* ===============================
             | 6️⃣ Call OpenAI (JSON MODE)
             =============================== */
            $response = Http::withToken($openAiKey)
                ->timeout(20)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $config['openai_model'] ?? 'gpt-4o',
                    'temperature' => 0.2,
                    'max_tokens' => 400,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => $messages
                ]);
         //   Log::info('AI RAW messages', ['messages' => $messages]);

            if (!$response->successful()) {
                throw new \Exception('OpenAI request failed');
            }

            /* ===============================
             | 7️⃣ Parse AI Response (SAFE)
             =============================== */
            $content = $response->json('choices.0.message.content');
         

            // Remove markdown if any
            $clean = preg_replace('/```json|```/i', '', $content);

            // Try extracting JSON
            if (preg_match('/\{.*\}/s', $clean, $matches)) {
                $json = $matches[0];
                $result = json_decode($json, true);

              //    Log::info('AI RAW result', ['response' => $result]);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->normalizeResponse($result);
                }
            }

            // 🔹 Fallback: AI returned plain text (follow-up question)
            return $this->fallbackResponse(
                trim($content)
            );

        } catch (\Throwable $e) {
            Log::error('AI Error', [
                'error' => $e->getMessage()
            ]);

            return $this->fallbackResponse(
                'Please consult a general physician.'
            );
        }
    }

    /* ===============================
     | Helpers
     =============================== */

    private function normalizeResponse(array $result): array
    {
        return [
            'show_doctors' => $result['show_doctors'] ?? false,
            'urgency'    => $result['urgency'] ?? 'low',
            'reply'      => $result['reply'] ?? 'Please consult a doctor.',
            'filters'    => $result['filters'] ?? $this->emptyFilters()
        ];
    }

    private function emptyFilters(): array
    {
        return [
            'department' => null,
            'doctor_name' => null,
            'clinic_title' => null,
            'gender' => null,
            'min_experience' => null,
            'min_rating' => null,
            'min_reviews' => null,
            'min_appointments' => null
        ];
    }

    private function fallbackResponse(string $reply): array
    {
        return [
            'urgency' => 'low',
            'reply' => $reply,
            'filters' => $this->emptyFilters()
        ];
    }
}
