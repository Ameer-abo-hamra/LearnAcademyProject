<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Teacher;
use App\Models\Student;

class TeacherAndStudentSeeder extends Seeder
{
    public function run(): void
    {
        // إنشاء معلم
        Teacher::create([
            'full_name'        => 'Ahmed Teacher',
            'email'            => 'teacher@example.com',
            'image'            => Storage::disk('teacher_image')->url('teacher1.png'),
            'specialization'   => 'Mathematics',
            'age'              => 35,
            'gender'           => 1,
            'password'         => Hash::make('password123'),
            'username'         => 'ahmed_teacher',
            'activation_code'  => Str::random(10),
            'is_active'        => true,
            'admin_activation' => true,
        ]);

        // إنشاء طالب
        Student::create([
            'full_name'        => 'Sara Student',
            'email'            => 'student@example.com',
            'image'            => Storage::disk('student_image')->url('student1.png'),
            'age'              => 22,
            'gender'           => 0,
            'free_points'      => 50,
            'paid_points'      => 1000,
            'password'         => Hash::make('password123'),
            'username'         => 'sara_student',
            'activation_code'  => Str::random(10),
            'is_active'        => true,
            'admin_activation' => true,
        ]);
    }
}
