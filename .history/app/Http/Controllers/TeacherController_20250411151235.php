<?php

namespace App\Http\Controllers;

use App\Http\Requests\createTeacher;
use App\Models\Teacher;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    use ResponseTrait ;
    public function sign_up(createTeacher $createTeacher)
    {
        $validated_teacher = $createTeacher->validated();
        Teacher::create($validated_teacher);
        return $this->returnData($validated_teacher) ;
    }
}
