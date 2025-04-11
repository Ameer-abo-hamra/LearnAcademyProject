<?php

namespace App\Http\Controllers;

use App\Http\Requests\createTeacher;
use App\Models\Teacher;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Validator;

class TeacherController extends Controller
{
    use ResponseTrait;
    public function signUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:50|unique:subscribers',
            'password' => 'required|string|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/',
            'full_name' => 'required|string',
            'username' => 'required|min:4|max:20',
        ]);


        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            $code = sendEmail($request->email);
            if ($code) {

                $subscriber = Subscriber::create([
                    'full_name' => $request->full_name,
                    'password' => Hash::make($request->password),
                    'email' => $request->email,
                    'username' => $request->username,
                    "verification_code" => $code,
                    "country_code_id" => $request->country_code_id,
                    "mobile" => $request->mobile
                ]);



                return $this->returnSuccess('Your account has been created successfully.', 200);
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }

        return $this->returnError("This email does not exist.");
    }
}
