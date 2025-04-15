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


    public function makeCourse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:4048',
            'level' => 'required|in:0,1,2', // فقط 0 أو 1 أو 2
            'teacher_id' => 'required|exists:teachers,id',
            // 'specilization_id' => 'nullable|exists:specilizations,id',
            'point_to_enroll' => 'required|integer|min:0',
            'is_quizes_auto_generated' => 'required|boolean',
            'skills' => 'nullable|array',
            'skills.*' => 'exists:skills,id',
            'requirements' => 'requirement|array',
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
                'image' => "/",
                'level' => $request->level,
                'teacher_id' => $request->teacher_id,
                // 'specilization_id' => $request->specilization_id,
                'point_to_enroll' => $request->point_to_enroll,
            ]);
            $course->image = imageUpload($request, $course->id, "course_image");
            $course->save();
            if ($request->has('skills')) {
                $course->skills()->sync($request->skills);
            }

            if ($request->has('requirements')) {
                $course->requirements()->sync($request->requirements);
            }

            DB::commit();

            return $this->returnSuccess('Course created successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError('Something went wrong: ' . $e->getMessage());
        }
    }


}
