<?php

namespace App\Http\Controllers;
use App\Traits\ResponseTrait;
use Validator ;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use ResponseTrait ;

    public function makeCourse(Request $request) {
        $validator = Validator::make($request->all(), [
            'is_quizes_auto_generated' => ['required', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'specilization_id' => ['nullable', 'exists:specilizations,id'],
        ]);
        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

    }

}
