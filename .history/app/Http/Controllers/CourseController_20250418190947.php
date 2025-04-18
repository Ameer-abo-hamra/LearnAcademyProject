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
            'level' => 'required|in:0,1,2',
            'teacher_id' => 'required|exists:teachers,id',
            'point_to_enroll' => 'required|integer|min:0',
            'points_earned' => 'required|integer|min:0',
            'skills' => 'required|array',
            'skills.*' => 'exists:skills,id',
            'aquirements' => 'required|array',
            'aquirements.*' => 'exists:skills,id',
            'category_id' => 'required|exists:categories,id',
        ]);

        // ⛔ check conditional rule manually
        $validator->after(function ($validator) use ($request) {
            $pointsToEnroll = (int) $request->point_to_enroll;
            $pointsEarned = (int) $request->points_earned;

            if ($pointsToEnroll >= 0 && $pointsToEnroll <= 10 && $pointsEarned > 10) {
                $validator->errors()->add('points_earned', 'You cannot assign more than 10 points to a free course.');
            }
        });

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
                'category_id' => $request->category_id,
                'point_to_enroll' => $request->point_to_enroll,
                'points_earned' => $request->points_earned,
            ]);

            $course->image = imageUpload($request, $course->id, "course_image");
            $course->save();

            $course->skills()->sync($request->skills);
            $course->aquirements()->sync($request->aquirements);

            DB::commit();

            return $this->returnSuccess('Course created successfully', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError('Something went wrong: ' . $e->getMessage());
        }
    }



}
