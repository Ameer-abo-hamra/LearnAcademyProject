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
            'id' => 'required|exists:categories,id',
        ]);
        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }
    }
}
