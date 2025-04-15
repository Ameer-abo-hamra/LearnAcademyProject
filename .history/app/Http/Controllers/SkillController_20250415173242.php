<?php

namespace App\Http\Controllers;
use App\Traits\ResponseTrait;
use Validator;
use Illuminate\Http\Request;

class SkillController extends Controller
{
use ResponseTrait ; 
    public function getSkillFromCategory($category_id) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:50',
            'id' => 'required|exists:teachers,id',
        ]);
        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }
    }
}
