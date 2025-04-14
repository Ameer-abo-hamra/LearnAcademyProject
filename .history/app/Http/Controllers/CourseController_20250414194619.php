<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CourseController extends Controller
{

    public function makeCourse(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:50',
        ]);
        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

    }

}
