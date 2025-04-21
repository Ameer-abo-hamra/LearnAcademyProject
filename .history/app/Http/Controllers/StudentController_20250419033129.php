<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Specilization;
use Illuminate\Http\Request;
use App\Http\Requests\createStudent;
use App\Models\Student;
use App\Traits\ResponseTrait;
use Hash;
use Validator;
use Auth;
class StudentController extends Controller
{
    use ResponseTrait;
    public function signUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:50|unique:Students',
            'password' => 'required|string|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/',
            'full_name' => 'required|string',
            'username' => 'required|min:4|max:20',
        ]);


        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $code = sendEmail($request);
            if ($code) {

                $Student = Student::create([
                    'full_name' => $request->full_name,
                    'password' => Hash::make($request->password),
                    'email' => $request->email,
                    'username' => $request->username,
                    "activation_code" => $code,
                ]);



                return $this->returnData('Your account has been created successfully please activate your account now .', $Student, 200);
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }

        return $this->returnError("your email does not exist :(");
    }

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
            'username' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $cre = $request->only('password', 'username');

            $token = Auth::guard('student')->attempt($cre);

            if ($token) {

                $Student = Auth::guard('student')->user();

                $Student->token = $token;

                return $this->returnData('', $Student, 200, );

            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());

        }
        return $this->returnError('your data is invalid');
    }
    public function logout()
    {

        Auth::guard('student')->logout();
        return $this->returnSuccess('your are logged-out successfully');
    }
    public function activate(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'activation_code' => 'required|string|min:6|max:6',
            'id' => 'required|exists:students,id',
        ]);


        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $Student = Student::find($request->id);
            if ($Student->activation_code === $request->activation_code) {
                $Student->is_active = true;
                $Student->save();
                return $this->returnSuccess('your account activated successfully :)');
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());

        }
        return $this->returnError("your code is not correct ");
    }

    public function resend(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:50',
            'id' => 'required|exists:students,id',
        ]);
        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $Student = Student::find($request->id);
            if ($code = sendEmail($Student)) {
                $Student->activation_code = $code;
                $Student->save();
                return $this->returnSuccess("we sent your activation code  succesfully ");
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());

        }
        return $this->returnError(msgErorr: "this email does not exist");


    }
    public function searchCoursesAndSpecializations(Request $request)
    {
        $courseFilters = $request->input('course_filters', []);
        $specFilters = $request->input('specialization_filters', []);

        $courses = collect(); // افتراضي
        $specializations = collect(); // افتراضي

        $coursePagination = null;
        $specPagination = null;

        // 🟦 الكورسات (فقط إذا أرسلت course_filters)
        if (!empty($courseFilters)) {
            $sortBy = $courseFilters['sort_by'] ?? 'created_at';
            $sortDir = in_array(strtolower($courseFilters['sort_order'] ?? 'desc'), ['asc', 'desc']) ? strtolower($courseFilters['sort_order']?? 'desc') : 'desc';
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

        // 🟨 التخصصات (فقط إذا أرسلت specialization_filters)
        if (!empty($specFilters)) {
            $sortBy = $specFilters['sort_by'] ?? 'created_at';
            $sortDir = in_array(strtolower($specFilters['sort_order'] ?? 'desc'), ['asc', 'desc']) ? strtolower($specFilters['sort_order']?? 'desc') : 'desc';
            $perPage = is_numeric($specFilters['per_page'] ?? null) ? (int) $specFilters['per_page'] : 10;

            $query = Specilization::query();

            $query->when($specFilters['title'] ?? null, fn($q, $v) => $q->where('title', 'like', "%$v%"))
                  ->when(isset($specFilters['is_completed']), fn($q) => $q->where('is_completed', $specFilters['is_completed']))
                  ->orderBy($sortBy, $sortDir);

            $specPagination = $query->paginate($perPage);
            $specializations = $specPagination->getCollection();
        }

        // 🔁 بناء meta حسب الموجود
        $meta = [];

        if ($coursePagination && $specPagination) {
            $meta = [
                'total' => $coursePagination->total() + $specPagination->total(),
                'per_page' => $coursePagination->perPage(), // نفترض نفس القيمة
                'current_page' => $coursePagination->currentPage(),
                'last_page' => max($coursePagination->lastPage(), $specPagination->lastPage()),
            ];
        } elseif ($coursePagination) {
            $meta = [
                'total' => $coursePagination->total(),
                'per_page' => $coursePagination->perPage(),
                'current_page' => $coursePagination->currentPage(),
                'last_page' => $coursePagination->lastPage(),
            ];
        } elseif ($specPagination) {
            $meta = [
                'total' => $specPagination->total(),
                'per_page' => $specPagination->perPage(),
                'current_page' => $specPagination->currentPage(),
                'last_page' => $specPagination->lastPage(),
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'نتائج البحث',
            'data' => [
                'courses' => $courses,
                'specializations' => $specializations,
            ],
            'meta' => $meta
        ]);
    }



}
