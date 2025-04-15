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
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:teachers',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:4048',
            'specialization' => 'required|string|max:255',
            'age' => 'required|integer|min:16|max:100',
            'gender' => 'required|in:0,1', // 0 = Female, 1 = Male (مثلاً)
            'password' => 'required|string|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
            'username' => 'required|string|min:4|max:50|unique:teachers',
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
                    "age"=>$request->age ,
                    "specialization"=>$request->specialization ,
                    "gender" => $request->gender ,
                ]);

                $teacher->image = imageUpload($request , $)

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
            'activation_code' => 'required|string|min:6|max:6',
            'id' => 'required|exists:teachers,id',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $teacher = Teacher::find($request->id);
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
            'id' => 'required|exists:teachers,id',
        ]);
        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $teacher =Teacher::find($request->id);
            if ($code = sendEmail($teacher)) {
                $teacher->activation_code = $code;
                $teacher->save();
                return $this->returnSuccess("we sent your activation code  succesfully ");
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());

        }
        return $this->returnError(msgErorr: "this email does not exist");


    }

}
