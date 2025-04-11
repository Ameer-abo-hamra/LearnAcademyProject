<?php

namespace App\Http\Controllers;

use App\Http\Requests\createTeacher;
use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function sign_up(Request $request)
    {
        $validated_teacher = $request->validated();
        Teacher::create($validated_teacher);
        return true;

    }
}
