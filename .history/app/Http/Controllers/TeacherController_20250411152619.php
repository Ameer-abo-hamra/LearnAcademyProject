<?php

namespace App\Http\Controllers;

use App\Http\Requests\createTeacher;
use App\Models\Teacher;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    use ResponseTrait;
    public function sign_up(Request $request)
    {
        $data = $request->validated();

        $user = Teacher::create([
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
        return $this->returnData("", $$user);
    }
}
