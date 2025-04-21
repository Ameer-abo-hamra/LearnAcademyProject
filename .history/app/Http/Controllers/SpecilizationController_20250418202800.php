<?php

namespace App\Http\Controllers;

use App\Traits\ResponseTrait;
use DB;
use Illuminate\Http\Request;
use Validator ;
class SpecilizationController extends Controller
{
    use ResponseTrait ;
    public function createSpecialization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'exists:courses,id',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        DB::beginTransaction();

        try {
            // إنشاء التخصص
            $specialization = specia::create([
                'title' => $request->title,
            ]);

            $courseIds = $request->course_ids;
            $categoryIds = [];

            foreach ($courseIds as $courseId) {
                $course = Course::find($courseId);

                // ربط الكورس بالتخصص
                $specialization->courses()->attach($course->id, ['is_completed' => false]);

                // أخذ الـ category الخاص بكل كورس
                if ($course->category_id) {
                    $categoryIds[] = $course->category_id;
                }
            }

            // إزالة التكرار وربط الـ categories بالتخصص
            $uniqueCategoryIds = array_unique($categoryIds);
            $specialization->categories()->sync($uniqueCategoryIds);

            DB::commit();

            return $this->returnSuccess('Specialization created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError('Error: ' . $e->getMessage(), 500);
        }
    }

}
