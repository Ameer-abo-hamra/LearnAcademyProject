<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Skill;
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

        $category = Category::find($category_id);

        if (!$category) {
            return $this->returnError("Category not found.");
        }

        $skills = $category->skills->makeHidden(['created_at', 'updated_at', 'category_id']);

        return $this->returnData("Skills fetched successfully", $skills);
    }
 public function index()
    {
        $skills = Skill::with('category:id,title')
            ->select('id', 'title', 'category_id')
            ->get()
            ->map(function ($skill) {
                return [
                    'id' => $skill->id,
                    'title' => $skill->title,
                    'category' => [
                        'id' => $skill->category->id,
                        'title' => $skill->category->title
                    ]
                ];
            });

        return $this->returnData('skills', $skills);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id'
        ]);

        $skill = Skill::create($request->only('title', 'category_id'));
        return $this->returnSuccess("Skill created successfully", $skill);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id'
        ]);

        $skill = Skill::find($id);
        if (!$skill) return $this->returnError("Skill not found", 404);

        $skill->update($request->only('title', 'category_id'));
        return $this->returnSuccess("Skill updated successfully");
    }

   public function destroy($id)
{
    $skill = Skill::find($id);
    if (!$skill) {
        return $this->returnError("Skill not found", 404);
    }

    // تحقق من الارتباطات
    if (
        $skill->courses()->exists() ||
        $skill->aquirements()->exists() ||
        $skill->specializations()->exists()
    ) {
        return $this->returnError("Cannot delete: Skill is linked to courses, aquirements, or specializations.");
    }

    $skill->delete();
    return $this->returnSuccess("Skill deleted successfully");
}

}
