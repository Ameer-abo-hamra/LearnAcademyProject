<?php

namespace App\Http\Controllers;

use App\Http\Requests\createTeacher;
use App\Models\Teacher;
use App\Traits\ResponseTrait;
use Hash;
use Illuminate\Http\Request;
use Validator;
use Auth;
class TeacherController extends Controller
{
    use ResponseTrait;
    public function signUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:50|unique:teachers',
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

                $teacher = Teacher::create([
                    'full_name' => $request->full_name,
                    'password' => Hash::make($request->password),
                    'email' => $request->email,
                    'username' => $request->username,
                    "activation_code" => $code,
                ]);



                return $this->returnData('Your account has been created successfully please activate your account now .', $teacher, 200);
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

            $token = Auth::guard('teacher')->attempt($cre);

            if ($token) {

                $teacher = Auth::guard('teacher')->user();

                $teacher->token = $token;

                return $this->returnData('', $teacher, 200, );

            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());

        }
        return $this->returnError('your data is invalid');
    }
    public function logout()
    {

        Auth::guard('teacher')->logout();
        return $this->returnSuccess('your are logged-out successfully');
    }
    public function activate(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'activation_code' => 'required | string | min:6|max:6'
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $teacher = Auth::guard('teacher')->user();
            if ($teacher->activation_code === $request->activation_code) {
                $teacher->is_active = true;
                $teacher->save();
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
        ]);
        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $teacher = Auth::guard("teacher")->user();
            if ($code = sendEmail($teacher)) {
                $teacher->activation_code = $code;
                $teacher->save();
                return $this->returnSuccess("we sent your verification code  succesfully ");
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());

        }
        return $this->returnError(msgErorr: "this email does not exist");


    }
    function getCurrentUserGuard(): ?string {
        foreach (array_keys(config('auth.guards')) as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        return null; // لا يوجد مستخدم موثّق حاليًا
    }
}
