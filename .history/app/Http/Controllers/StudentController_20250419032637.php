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
        // 🔍 قراءة الفلاتر من البنية اللي ترسلها
        $courseFilters = $request->input('course_filters', []);
        $specializationFilters = $request->input('specialization_filters', []);

        // إعدادات الترتيب والتقسيم للكورسات
        $courseSortBy = $courseFilters['sort_by'] ?? 'created_at';
        $courseSortDir = in_array(strtolower($courseFilters['sort_order'] ?? 'desc'), ['asc', 'desc'])
            ? strtolower($courseFilters['sort_order']??)
            : 'desc';
        $coursePerPage = is_numeric($courseFilters['per_page'] ?? null) ? (int) $courseFilters['per_page'] : 10;

        // إعدادات الترتيب والتقسيم للتخصصات
        $specPerPage = is_numeric($specializationFilters['per_page'] ?? null) ? (int) $specializationFilters['per_page'] : 10;
        $specSortBy = 'created_at';
        $specSortDir = 'desc';

        // 📦 الكورسات
        $coursesPaginated = Course::when($courseFilters['name'] ?? null, fn($q, $v) => $q->where('name', 'like', "%$v%"))
            ->when($courseFilters['status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($courseFilters['level'] ?? null, fn($q, $v) => $q->where('level', $v))
            ->when($courseFilters['category_id'] ?? null, fn($q, $v) => $q->where('category_id', $v))
            ->when($courseFilters['specilization_id'] ?? null, fn($q, $v) => $q->where('specilization_id', $v))
            ->when($courseFilters['min_point_to_enroll'] ?? null, fn($q, $v) => $q->where('point_to_enroll', '>=', $v))
            ->when($courseFilters['max_point_to_enroll'] ?? null, fn($q, $v) => $q->where('point_to_enroll', '<=', $v))
            ->when($courseFilters['min_points_earned'] ?? null, fn($q, $v) => $q->where('points_earned', '>=', $v))
            ->when($courseFilters['max_points_earned'] ?? null, fn($q, $v) => $q->where('points_earned', '<=', $v))
            ->orderBy($courseSortBy, $courseSortDir)
            ->paginate($coursePerPage);

        // 📦 التخصصات
        $specializationsPaginated = Specilization::when($specializationFilters['title'] ?? null, fn($q, $v) => $q->where('title', 'like', "%$v%"))
            ->when(isset($specializationFilters['is_completed']) ? $specializationFilters['is_completed'] : null, fn($q, $v) => $q->where('is_completed', $v))
            ->orderBy($specSortBy, $specSortDir)
            ->paginate($specPerPage);

        // البيانات
        $data = [
            'courses' => $coursesPaginated->items(),
            'specializations' => $specializationsPaginated->items()
        ];

        // تمرير الـ meta
        return self::returnData('نتائج البحث', $data, 200, [$coursesPaginated, $specializationsPaginated]);
    }


}
