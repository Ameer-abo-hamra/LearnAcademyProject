<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SessionController extends Controller
{
      public function createSession(Request $request)
    {
        $user_id = u("student")->id ;

        $response = Http::withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('http://localhost:8086/api/v1/sessions', [
            'video_id'   => (string)$request['video_id'],
            "session"
            'user_id'    =>(string) $user_id,
        ]);

        // 3. إذا نجح الطلب، أعدّ الـ JSON و 201
        if ($response->successful()) {
            return response()->json(
                $response->json(),
                201
            );
        }

        // 4. إذا فشل، أعدّ رسالة خطأ مع كود الاستجابة الأصلي
        return response()->json([
            'message' => $response->body(),
            'error'   => $response->json(),
        ], $response->status());

        // store session id from response
    }
}
