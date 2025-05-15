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
                    "age" => $request->age,
                    "specialization" => $request->specialization,
                    "gender" => $request->gender,
                    "image" => "/"
                ]);

                $teacher->image = imageUpload($request, $teacher->id, "teacher_image");
                $teacher->save();
                $teacher->image_link = $teacher->image_url;
                return $this->returnData('Your account has been created successfully please activate your account now .', $teacher, 200);
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }

        return $this->returnError("your email does not exist :(");
    }

    public function updateProfile(Request $request)
    {
        $teacher = u("teacher"); // أو u("teacher") إذا كنت تستخدمها

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:teachers,email,' . $teacher->id,
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:4048',
            'specialization' => 'sometimes|required|string|max:255',
            'age' => 'sometimes|required|integer|min:16|max:100',
            'gender' => 'sometimes|required|in:0,1',
            'username' => 'sometimes|required|string|min:4|max:50|unique:teachers,username,' . $teacher->id,
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            if ($request->has('full_name'))
                $teacher->full_name = $request->full_name;
            if ($request->has('email'))
                $teacher->email = $request->email;
            if ($request->has('username'))
                $teacher->username = $request->username;
            if ($request->has('specialization'))
                $teacher->specialization = $request->specialization;
            if ($request->has('age'))
                $teacher->age = $request->age;
            if ($request->has('gender'))
                $teacher->gender = $request->gender;

            if ($request->hasFile('image')) {
                $teacher->image = imageUpload($request, $teacher->id, "teacher_image");
            }

            $teacher->save();
            $teacher->image_link = $teacher->image_url;

            return $this->returnData('Your profile has been updated successfully.', $teacher, 200);

        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

    public function getProfile()
    {
        try {
            $teacher = u("teacher"); // أو u("teacher") إذا عندك هالـ helper

            if (!$teacher) {
                return $this->returnError("Teacher not authenticated", 401);
            }

            // إضافة رابط الصورة
            $teacher->image_link = $teacher->image_url;

            return $this->returnData("Teacher profile fetched successfully", $teacher->makeHidden("password"));

        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
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
            $teacher = Teacher::find($request->id);
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

    public function getTeacherNotifications()
{
    $teacher = u('teacher');

    $notifications = $teacher->notifications()
        ->latest()
        ->paginate(10, ['title', 'body']);

    return $this->returnData('Notifications retrieved successfully', $notifications);
}

}
