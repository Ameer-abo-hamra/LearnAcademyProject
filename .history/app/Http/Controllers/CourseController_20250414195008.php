<?php

namespace App\Http\Controllers;
use App\Models\Course;
use App\Traits\ResponseTrait;
use Validator;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    use ResponseTrait;

    public function makeCourse(Request $request)
    {
        // التحقق من البيانات
        $validator = Validator::make($request->all(), [
            'is_quizes_auto_generated' => ['required', 'boolean'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'teacher_id' => ['required', 'exists:teachers,id'],
            'specilization_id' => ['nullable', 'exists:specilizations,id'],
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            // إنشاء الكورس
            $course = Course::create([
                'is_quizes_auto_generated' => $request->is_quizes_auto_generated,
                'name' => $request->name,
                'description' => $request->description,
                'teacher_id' => $request->teacher_id,
                'specilization_id' => $request->specilization_id,
            ]);

            return $this->returnSuccess("Course created successfully", $course);
        } catch (\Exception $e) {
            return $this->returnError("Something went wrong: " . $e->getMessage());
        }
    }


}
