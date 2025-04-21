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
        // الفلاتر المسموحة
        $filters = $request->only([
            'course_name',
            'course_level',
            'course_status',
            'min_points_to_enroll',
            'max_points_to_enroll',
            'min_points_earned',
            'max_points_earned',
            'specialization_title',
            'is_completed',
            'sort_by',
            'sort_direction',
            'per_page'
        ]);

        // إعدادات الترتيب والتقسيم
        $sortBy = $filters['sort_by'] ?? 'created_at';

        // التأكد من سلامة قيمة sort_direction
        $rawSortDir = strtolower($filters['sort_direction'] ?? 'desc');
        $sortDir = in_array($rawSortDir, ['asc', 'desc']) ? $rawSortDir : 'desc';

        // التحقق من per_page
        $perPage = is_numeric($filters['per_page'] ?? null) ? (int) $filters['per_page'] : 10;

        // 📦 الكورسات
        $courses = Course::with(['skills', 'category', 'specilizations'])
            ->when($filters['course_name'] ?? null, fn($q, $v) => $q->where('name', 'like', "%$v%"))
            ->when($filters['course_status'] ?? null, fn($q, $v) => $q->where('status', $v))
            ->when($filters['course_level'] ?? null, fn($q, $v) => $q->where('level', $v))
            ->when($filters['min_points_to_enroll'] ?? null, fn($q, $v) => $q->where('point_to_enroll', '>=', $v))
            ->when($filters['max_points_to_enroll'] ?? null, fn($q, $v) => $q->where('point_to_enroll', '<=', $v))
            ->when($filters['min_points_earned'] ?? null, fn($q, $v) => $q->where('points_earned', '>=', $v))
            ->when($filters['max_points_earned'] ?? null, fn($q, $v) => $q->where('points_earned', '<=', $v))
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);





        return $this->returnData("results", $courses = $courses->getCollection();, 200, $meta);
    }


}
