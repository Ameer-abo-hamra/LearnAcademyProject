<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SessionController extends Controller
{
    use ResponseTrait;
    public function createSession(Request $request)
    {
        $user_id = u("student")->id;

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('http://localhost:8086/api/v1/sessions', [
                    'video_id' => (string) $request['video_id'],
                    'user_id' => (string) $user_id,
                ]);

        // 3. إذا نجح الطلب، أعدّ الـ JSON و 201
        if ($response->successful()) {
            $session_id = $response->json()['session_id'];
            u("student")->sessions()->create([
                "session__id" => $session_id,
            ]);

            return $this->returnData("your session id is : ", $session_id);
        }


        // 4. إذا فشل، أعدّ رسالة خطأ مع كود الاستجابة الأصلي
        return $this->returnError($response->body() . $response->json());



        // store session id from response
    }


    public function sendMessageToVideoBot(Request $request)
    {
        $validated = $request->validate([
            'session_id'     => 'required|string',
            'message'        => 'required|string',
            'user_language'  => 'required|string|size:2', // مثال: 'en' أو 'ar'
        ]);

        try {
            $response = Http::post("http://localhost:8086/api/v1/sessions/{$validated['session_id']}/messages", [
                'message'       => (string)$validated['message'],
                'user_language' =>(string) $validated['user_language'],
                'user_id'       =>(string) u("student")->id,
            ]);

            if ($response->failed()) {
                return $this->returnError(
                    'فشل في إرسال الرسالة إلى VideoBot.',
                    $response->status(),
                    $response->json()
                );
            }

            return $this->returnData(
                'تم إرسال الرسالة بنجاح.',
                $response->json(),
                200
            );

        } catch (\Exception $e) {
            return $this->returnError(
                'حدث خطأ أثناء إرسال الرسالة.',
                500,
                ['exception' => $e->getMessage()]
            );
        }
    }
}
