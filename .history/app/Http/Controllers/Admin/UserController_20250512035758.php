<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ResponseTrait;
use DB;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;
use Str;
use Validator;

class UserController extends Controller
{
    use ResponseTrait;
    // ======= STUDENTS =======

    public function createStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:50|unique:students',
            'password' => 'required|string|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/',
            'full_name' => 'required|string',
            'username' => 'required|string|min:4|max:50|unique:students',
            'age' => 'required|integer|min:16|max:100',
            'gender' => 'required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4048',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            $code = Str::random(6); // كود التفعيل من صنع النظام

            $student = Student::create([
                'full_name' => $request->full_name,
                'password' => Hash::make($request->password),
                'email' => $request->email,
                'username' => $request->username,
                'activation_code' => $code,
                'age' => $request->age,
                'gender' => $request->gender,
                'is_active' => true, // أدمن أنشأ الحساب فهو مفعل تلقائياً
                'admin_activation' => true,
                'image' => '', // نحدّثه إذا وجدت صورة
            ]);

            if ($request->hasFile("image")) {
                $image = imageUpload($request, $student->id, "student_image");
                $path = assetFromDisk("student_image", $image);
                $student->image = $path;
                $student->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Student account created successfully by admin.',
                'data' => $student
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateStudent(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'email' => 'sometimes|required|string|email|max:50|unique:students,email,' . $student->id,
            'password' => 'nullable|string|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{6,}$/',
            'full_name' => 'sometimes|required|string',
            'username' => 'sometimes|required|string|min:4|max:50|unique:students,username,' . $student->id,
            'age' => 'sometimes|required|integer|min:16|max:100',
            'gender' => 'sometimes|required|in:0,1',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:4048',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first(), 422);
        }

        try {
            DB::beginTransaction();

            $student->fill($request->except(['password', 'image']));

            if ($request->filled('password')) {
                $student->password = Hash::make($request->password);
            }

            if ($request->hasFile('image')) {
                $image = imageUpload($request, $student->id, "student_image");
                $path = assetFromDisk("student_image", $image);
                $student->image = $path;
            }

            $student->save();

            DB::commit();

            return $this->returnData('Student updated successfully.', $student);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();
        return $this->returnSuccess("Student deleted");
    }

    // ======= TEACHERS =======

    public function createTeacher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:teachers',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:4048',
            'specialization' => 'required|string|max:255',
            'age' => 'required|integer|min:16|max:100',
            'gender' => 'required|in:0,1',
            'password' => 'required|string|min:6|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
            'username' => 'required|string|min:4|max:50|unique:teachers',
        ]);

        if ($validator->fails()) {
            return $this->returnError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $code = Str::random(6);

            $teacher = Teacher::create([
                'full_name' => $request->full_name,
                'password' => Hash::make($request->password),
                'email' => $request->email,
                'username' => $request->username,
                'activation_code' => $code,
                'age' => $request->age,
                'specialization' => $request->specialization,
                'gender' => $request->gender,
                'image' => '/',
                'is_active' => true,
                'admin_activation' => true,
            ]);

            $teacher->image = imageUpload($request, $teacher->id, "teacher_image");
            $teacher->save();

            DB::commit();

            return $this->returnData("Teacher created successfully by admin.", $teacher);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->returnError($e->getMessage());
        }
    }

    public function updateTeacher(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->update($request->except(['password']));

        if ($request->has('password')) {
            $teacher->password = Hash::make($request->password);
            $teacher->save();
        }

        return response()->json(['message' => 'Teacher updated', 'data' => $teacher]);
    }

    public function deleteTeacher($id)
    {
        $teacher = Teacher::findOrFail($id);
        $teacher->delete();
        return response()->json(['message' => 'Teacher deleted']);
    }
}
