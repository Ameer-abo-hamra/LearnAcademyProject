<?php

namespace App\Http\Controllers;
use App\Models\Course;
use App\Traits\ResponseTrait;
use DB;
use Validator;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use ResponseTrait;

    use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Course;

public function makeCourse(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'description' => 'required|string',
        'image' => 'required|string|max:255',
        'level' => 'required|in:0,1,2', // فقط 0 أو 1 أو 2
        'teacher_id' => 'required|exists:teachers,id',
        'specilization_id' => 'nullable|exists:specilizations,id',
        'point_to_enroll' => 'required|integer|min:0',
        'is_quizes_auto_generated' => 'required|boolean',
        'skills' => 'nullable|array',
        'skills.*' => 'exists:skills,id',
        'requirements' => 'nullable|array',
        'requirements.*' => 'exists:skills,id',
    ]);

    if ($validator->fails()) {
        return $this->returnError($validator->errors()->first());
    }

    DB::beginTransaction();

    try {
        $course = Course::create([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $request->image,
            'level' => $request->level,
            'teacher_id' => $request->teacher_id,
            'specilization_id' => $request->specilization_id,
            'point_to_enroll' => $request->point_to_enroll,
            'is_quizes_auto_generated' => $request->is_quizes_auto_generated,
        ]);

        if ($request->has('skills')) {
            $course->skills()->sync($request->skills);
        }

        if ($request->has('requirements')) {
            $course->requirements()->sync($request->requirements);
        }

        DB::commit();

        return $this->returnuccess('Course created successfully', $course->load('skills', 'requirements'));
    } catch (\Exception $e) {
        DB::rollBack();
        return $this->returnError('Something went wrong: ' . $e->getMessage());
    }
}


}
