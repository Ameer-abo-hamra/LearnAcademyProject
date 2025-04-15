<?php

namespace App\Http\Controllers;
use App\Traits\ResponseTrait;
use Validator;
use Illuminate\Http\Request;

class SkillController extends Controller
{
use ResponseTrait ;
    public function getSkillFromCategory($category_id) {
        if(!$category_id) {
            return $this->returnError("where is the id");
        }
    }
}
