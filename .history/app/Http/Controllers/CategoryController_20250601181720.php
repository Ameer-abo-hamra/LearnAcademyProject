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
        return $this->returnData("categories", Category::all()->makeHidden(['created_at', 'updated_at']));

    }
    public function index()
    {
        $categories = Category::select('id', 'title')->get();
        return $this->returnData('categories', $categories);
    }

    public function store(Request $request)
    {
        $request->validate(['title' => 'required|string|max:255']);
        $category = Category::create(['title' => $request->title]);
        return $this->returnSuccess("Category created successfully", $category);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['title' => 'required|string|max:255']);
        $category = Category::find($id);
        if (!$category)
            return $this->returnError("Category not found", 404);

        $category->update(['title' => $request->title]);
        return $this->returnSuccess("Category updated successfully");
    }

 public function destroy($id)
{
    $category = Category::find($id);
    if (!$category) {
        return $this->returnError("Category not found", 404);
    }

    if ($category->skills()->exists() || $category->courses()->exists()) {
        return $this->returnError("Cannot delete: Category is linked to skills or courses.");
    }

    $category->delete();
    return $this->returnSuccess("Category deleted successfully");
}

}
