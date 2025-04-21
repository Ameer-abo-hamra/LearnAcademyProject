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
    public function search(Request $request)
    {
        $q = $request->input('q');
        $minPoints = $request->input('min_points');
        $maxPoints = $request->input('max_points');
        $minPoints_e = $request->input('min_points_e');
        $maxPoints_e = $request->input('max_points_e');
        $level = $request->input('level');
        $isCompleted = $request->input('is_completed');

        // ——————————————————————————————
        // 1) بحث في الدورات
        // ——————————————————————————————
        $courses = Course::query()
            // فلترة نقاط التسجيل
            ->when($minPoints, fn($qB) => $qB->where('point_to_enroll', '>=', $minPoints))
            ->when($maxPoints, fn($qB) => $qB->where('point_to_enroll', '<=', $maxPoints))
            ->when($minPoints_e, fn($qB) => $qB->where('points_earned', '>=', $minPoints_e))
            ->when($maxPoints_e, fn($qB) => $qB->where('points_earned', '<=', $maxPoints_e))
            ->when($level, fn($qB) => $qB->where('level', $level))
            // شرط الـ keyword
            ->when($q, function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        // بحث عن المهارة
                        ->orWhereHas('skills', function ($q2) use ($q) {
                            $q2->where('title', 'like', "%{$q}%");
                        })
                        // بحث عن التصنيف
                        ->orWhereHas('category', function ($q2) use ($q) {
                            $q2->where('title', 'like', "%{$q}%");
                        });
                });
            })
            ->get();

        // ——————————————————————————————
        // 2) بحث في التخصصات
        // ——————————————————————————————
        $specializations = Specilization::query()
            ->when(!is_null($isCompleted), fn($qB) => $qB->where('is_completed', $isCompleted))
            ->when($q, function ($qb) use ($q) {
                $qb->where(function ($sub) use ($q) {
                    $sub->where('title', 'like', "%{$q}%")
                        // إذا بحث عن مهارة، اجلب التخصصات المرتبطة
                        ->orWhereHas('skills', function ($q2) use ($q) {
                            $q2->where('title', 'like', "%{$q}%");
                        })
                        // إذا بحث عن تصنيف، اجلب التخصصات المرتبطة
                        ->orWhereHas('categories', function ($q2) use ($q) {
                            $q2->where('title', 'like', "%{$q}%");
                        });
                });
            })
            ->get();
            $data = [
                'courses' => $courses,
                'specializations' => $specializations,
            ]
        return  $this->returnData("" , $data) ;
    }




}
