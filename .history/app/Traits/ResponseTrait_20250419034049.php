<?php

namespace App\Traits;

use App\Models\Specilization;

trait ResponseTrait
{

    public static function returnError($msgErorr = "", $errorNumber = 400, $meta = []): \Illuminate\Http\JsonResponse
    {

        return response()->json([
            "success" => false,
            "status" => $errorNumber,
            "message" => $msgErorr,
            "meta" => $meta
        ]);

    }
    public static function returnSuccess($msgSuccess = "", $succesNumber = 200, $meta = [])
    {

        return response()->json([
            "success" => true,
            "status" => $succesNumber,
            "message" => $msgSuccess,
            "meta" => $meta
        ]);

    }

    public function searchCoursesAndSpecializations(Request $request)
    {
        $courseFilters = $request->input('course_filters', []);
        $specFilters = $request->input('specialization_filters', []);

        $courses = collect();
        $specializations = collect();

        $coursePagination = null;
        $specPagination = null;

        // 🟦 معالجة الكورسات
        if (!empty($courseFilters)) {
            $sortBy = $courseFilters['sort_by'] ?? 'created_at';
            $sortDir = in_array(strtolower($courseFilters['sort_order'] ?? 'desc'), ['asc', 'desc']) ? strtolower($courseFilters['sort_order']) : 'desc';
            $perPage = is_numeric($courseFilters['per_page'] ?? null) ? (int) $courseFilters['per_page'] : 10;

            $query = Course::query();

            $query->when($courseFilters['name'] ?? null, fn($q, $v) => $q->where('name', 'like', "%$v%"))
                  ->when($courseFilters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
                  ->when($courseFilters['level'] ?? null, fn($q, $v) => $q->where('level', $v))
                  ->when($courseFilters['category_id'] ?? null, fn($q, $v) => $q->where('category_id', $v))
                  ->when($courseFilters['specilization_id'] ?? null, fn($q, $v) => $q->where('specilization_id', $v))
                  ->when($courseFilters['min_point_to_enroll'] ?? null, fn($q, $v) => $q->where('point_to_enroll', '>=', $v))
                  ->when($courseFilters['max_point_to_enroll'] ?? null, fn($q, $v) => $q->where('point_to_enroll', '<=', $v))
                  ->when($courseFilters['min_points_earned'] ?? null, fn($q, $v) => $q->where('points_earned', '>=', $v))
                  ->when($courseFilters['max_points_earned'] ?? null, fn($q, $v) => $q->where('points_earned', '<=', $v))
                  ->orderBy($sortBy, $sortDir);

            $coursePagination = $query->paginate($perPage);
            $courses = $coursePagination->getCollection();
        }

        // 🟨 معالجة التخصصات
        if (!empty($specFilters)) {
            $sortBy = $specFilters['sort_by'] ?? 'created_at';
            $sortDir = in_array(strtolower($specFilters['sort_order'] ?? 'desc'), ['asc', 'desc']) ? strtolower($specFilters['sort_order']) : 'desc';
            $perPage = is_numeric($specFilters['per_page'] ?? null) ? (int) $specFilters['per_page'] : 10;

            $query = Specilization::query();

            $query->when($specFilters['title'] ?? null, fn($q, $v) => $q->where('title', 'like', "%$v%"))
                  ->when(isset($specFilters['is_completed']), fn($q) => $q->where('is_completed', $specFilters['is_completed']))
                  ->orderBy($sortBy, $sortDir);

            $specPagination = $query->paginate($perPage);
            $specializations = $specPagination->getCollection();
        }

        // 🧠 تجميع البيانات النهائية
        $data = [
            'courses' => $courses,
            'specializations' => $specializations,
        ];

        // 🧾 meta كـ مصفوفة تمرر لـ returnData
        $meta = array_filter([$coursePagination, $specPagination]);

        return self::returnData("نتائج البحث", $data, 200, $meta);
    }



}
