<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Specilization;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Http\Request;
use Validator;
class SpecilizationController extends Controller
{
    use ResponseTrait;
    public function createSpecialization(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'is_completed' => 'required|boolean',
            'courses' => 'required|array|min:2',
            'courses.*' => 'exists:courses,id',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        DB::beginTransaction();

        try {
            // إنشاء التخصص
            $spec = Specilization::create([
                'title' => $request->title,
                'is_completed' => $request->is_completed,
            ]);

            // ربط الكورسات
            foreach ($request->courses as $course_id) {
                $spec->courses()->attach($course_id);
            }

            // ربط التصنيفات
            $categoryIds = Course::whereIn('id', $request->courses)->pluck('category_id')->unique();
            $spec->categories()->syncWithoutDetaching($categoryIds);

            // استخراج المهارات من الكورسات وربطها بالتخصص
            $skillIds = DB::table('course_skill')
                ->whereIn('course_id', $request->courses)
                ->pluck('skill_id')
                ->unique();

            $spec->skills()->syncWithoutDetaching($skillIds);

            DB::commit();
            return $this->returnSuccess('Specialization created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError($e->getMessage(), 500);
        }
    }

    public function getSpecializations(Request $request){

        $perPage = $request->input('per_page', 10); // القيمة الافتراضية هي 10
        // الحصول على رقم الصفحة من الـ Request (افتراضي 1)
        $page = $request->input('page_number', 1);
        $teacherSpec = u("teacher")->specializations->pa ;

    }

}
