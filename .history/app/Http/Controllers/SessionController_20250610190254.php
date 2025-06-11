<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SessionController extends Controller
{
    use ResponseTrait  ;
      public function createSession(Request $request)
    {
        $user_id = u("student")->id ;

        $response = Http::withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
        ])->post('http://localhost:8086/api/v1/sessions', [
            'video_id'   => (string)$request['video_id'],
            'user_id'    =>(string) $user_id,
        ]);

        // 3. إذا نجح الطلب، أعدّ الـ JSON و 201
        if ($response->successful()) {
            $session_id =  $response->json()['session_id'] ;
            u("student")->sessions()->create([
                ""
            ])
            return $this->returnData("your session id is : "  , );
        }


       ,
        // 4. إذا فشل، أعدّ رسالة خطأ مع كود الاستجابة الأصلي
        return $this->returnError($response->body() . $response->json()) ;



        // store session id from response
    }
}
