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
            'age' => 'required|integer|min:16|max:100',
            'gender' => 'required|in:0,1', // 0 = Female, 1 = Male (مثلاً)
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4048',
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
                    "age" => $request->age,
                    "gender" => $request->gender,
                    "image"=> ''
                ]);

                $Student->

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

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string',
            'min_points' => 'nullable|integer|min:0',
            'max_points' => 'nullable|integer|min:0|gte:min_points',
            'min_points_e' => 'nullable|integer|min:0',
            'max_points_e' => 'nullable|integer|min:0|gte:min_points_e',
            'level' => 'nullable|integer',
            'is_completed' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1',
            'page_number' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        $q = $request->input('q');
        $minPoints = $request->input('min_points');
        $maxPoints = $request->input('max_points');
        $minPointsE = $request->input('min_points_e');
        $maxPointsE = $request->input('max_points_e');
        $level = $request->input('level');
        $isCompleted = $request->input('is_completed');
        $perPage = $request->input('per_page', 10);
        $pageNumber = $request->input('page_number', 1);

        $coursesQuery = Course::query()
            ->when($minPoints, fn($qB) => $qB->where('point_to_enroll', '>=', $minPoints))
            ->when($maxPoints, fn($qB) => $qB->where('point_to_enroll', '<=', $maxPoints))
            ->when($minPointsE, fn($qB) => $qB->where('points_earned', '>=', $minPointsE))
            ->when($maxPointsE, fn($qB) => $qB->where('points_earned', '<=', $maxPointsE))
            ->when($level, fn($qB) => $qB->where('level', $level))
            ->when($q, function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhereHas(
                            'skills',
                            fn($q2) =>
                            $q2->where('title', 'like', "%{$q}%")
                        )
                        ->orWhereHas(
                            'category',
                            fn($q2) =>
                            $q2->where('title', 'like', "%{$q}%")
                        );
                });
            });

        $courses = $coursesQuery
            ->paginate($perPage, ['*'], 'page', $pageNumber);

        $specQuery = Specilization::query()
            ->when(
                !is_null($isCompleted),
                fn($qB) => $qB->where('is_completed', $isCompleted)
            )
            ->when($q, function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%{$q}%")
                        ->orWhereHas(
                            'skills',
                            fn($q2) =>
                            $q2->where('title', 'like', "%{$q}%")
                        )
                        ->orWhereHas(
                            'categories',
                            fn($q2) =>
                            $q2->where('title', 'like', "%{$q}%")
                        );
                });
            });

        $specializations = $specQuery
            ->paginate($perPage, ['*'], 'page', $pageNumber);

        // 5) تجهيز البيانات للإرسال
        $data = [
            'courses' => $courses->items(),
            'specializations' => $specializations->items(),
        ];

        // 6) إرجاع النتائج مع الميتا
        return $this->returnData(
            '',
            $data,
            200,
            [$courses, $specializations]
        );
    }

    public function courseEnroll(Request $request, $course_id)
    {

        $student = u("student");
        $course = Course::find($course_id)->first();
        $teacher_id = $course->teacher_id;
        $student->courses()->syncWithoutDetaching($course_id);
        $student->teachers()->syncWithoutDetaching($teacher_id);

    }
}






