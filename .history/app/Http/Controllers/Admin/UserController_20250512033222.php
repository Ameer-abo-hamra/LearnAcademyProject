<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function createStudent(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string',
            'email' => 'required|email|unique:students',
            'password' => 'required|string',
            'username' => 'required|string|unique:students',
            'activation_code' => 'required|string',
            'age' => 'required|integer',
            'image' => 'required|string',
            'gender' => 'required|in:0,1',
        ]);

        $student = Student::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Student created successfully', 'data' => $student]);
    }

    public function updateStudent(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $student->update($request->except(['password']));

        if ($request->has('password')) {
            $student->password = Hash::make($request->password);
            $student->save();
        }

        return response()->json(['message' => 'Student updated', 'data' => $student]);
    }

    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();
        return response()->json(['message' => 'Student deleted']);
    }

    // ======= TEACHERS =======

    public function createTeacher(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string',
            'email' => 'required|email|unique:teachers',
            'password' => 'required|string',
            'username' => 'required|string|unique:teachers',
            'activation_code' => 'required|string',
            'age' => 'required|integer',
            'image' => 'required|string',
            'gender' => 'required|in:0,1',
            'specialization' => 'required|string',
        ]);

        $teacher = Teacher::create([
            ...$validated,
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['message' => 'Teacher created successfully', 'data' => $teacher]);
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
