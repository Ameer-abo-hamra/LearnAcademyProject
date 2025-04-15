<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ResponseTrait;
    public function getAll()
    {
        return $this->returnData("categories", Category::all());

    }
}
