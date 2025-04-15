<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Traits\ResponseTrait;
use Validator;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    use ResponseTrait;
    public function getSkillFromCategory($category_id)
    {
        if (!$category_id) {
            return $this->returnError("where is the id");
        }

        $skills = Category::find($category_id)->first()->skills()->makeHidden(["created_at" ,"updated_at","categ"]);
        return $this->returnData(":)", $skills);
    }
}
