<?php

namespace App\Http\Controllers;

use App\Http\Requests\createTeacher;
use App\Models\Teacher;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    use ResponseTrait;
    public function sign_up(createTeacher $request)
    {
        $data = $request->validated();

        $user = Teacher::create([
            'full_name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
        return $this->returnData("", $$user);
    }
}
