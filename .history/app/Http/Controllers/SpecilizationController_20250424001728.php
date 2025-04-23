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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:4048',
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
                "teacher_id" => u("teacher")->id,
                'is_completed' => $request->is_completed,
                "image" => ""
            ]);
                $path = imageUpload($request , $spec->id , "specialization_image") ;
                $path = assetFromDisk("specialization_image" , $path) ;
                $spec
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

    public function updateSpecialization(Request $request, $id)
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
            $spec = Specilization::where('teacher_id', u("teacher")->id)->findOrFail($id);

            // تحديث بيانات التخصص
            $spec->update([
                'title' => $request->title,
                'is_completed' => $request->is_completed,
            ]);

            // تحديث ربط الكورسات
            $spec->courses()->sync($request->courses);

            // تحديث التصنيفات بناءً على الكورسات الجديدة
            $categoryIds = Course::whereIn('id', $request->courses)->pluck('category_id')->unique();
            $spec->categories()->sync($categoryIds);

            // تحديث المهارات المرتبطة بالكورسات
            $skillIds = DB::table('course_skill')
                ->whereIn('course_id', $request->courses)
                ->pluck('skill_id')
                ->unique();

            $spec->skills()->sync($skillIds);

            DB::commit();
            return $this->returnSuccess('Specialization updated successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError($e->getMessage(), 500);
        }
    }

    public function getSpecializations(Request $request)
    {

        $perPage = $request->input('per_page', 10);
        $page = $request->input('page_number', 1);
        $teacherSpec = u("teacher")->specializations()->paginate($perPage, ["*"], "page", $page);
        return $this->returnData("", $teacherSpec->items(), 200, $teacherSpec);
    }

    public function getSpecializationCourse($id)
    {
        $specialization = u("teacher")
            ->specializations()
            ->where("id", $id)
            ->with([
                "courses" => function ($q) {
                    $q->select("*"); // أو حدد الأعمدة إذا حاب
                }
            ])
            ->first();

        if (!$specialization) {
            return $this->returnError("Specialization not found or not assigned to you.");
        }
        $specialization->courses->each(function ($course) {
            unset($course->pivot);
        });
        return $this->returnData("", $specialization);
    }


}
