<?php

namespace App\Http\Controllers;

use App\Http\Requests\createTeacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function sign_up(createTeacher $createTeacher) {
        $validated_teacher = $createTeacher->validated() ;
            Tea
    }
}
