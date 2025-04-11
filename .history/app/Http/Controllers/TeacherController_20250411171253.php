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

    public function activate(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'verification_code' => 'required | string | min:6|max:6'
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $sub = Auth::guard('sub')->user();
            if ($sub->verification_code == $request->verification_code) {
                $sub->is_active = true;
                $sub->is_verified = true;
                $sub->save();
                return $this->returnSuccess('you are verified your account successfully :)');
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
            if (sendEmail($request->email)) {
                return $this->returnSuccess("we sent your verification code  succesfully ");
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());

        }
        return $this->returnError(msgErorr: "this email does not exist");


    }
}
