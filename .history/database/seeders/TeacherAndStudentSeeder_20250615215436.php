<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Teacher;
use App\Models\Student;
use Faker\Factory as Faker;

class TeacherAndStudentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // مسارات الصور الجاهزة
        $teacherImageUrl = Storage::disk('teacher_image')->url('teacher1.png');
        $studentImageUrl = Storage::disk('student_image')->url('student1.png');

        // تحقق يدوي إن الصورة موجودة فعليًا في المجلدات
        if (!file_exists(public_path('teacher_image/teacher1.png'))) {
            exit("❌ صورة المعلم teacher1.png غير موجودة في public/teacher_image/");
        }

        if (!file_exists(public_path('student_image/student1.png'))) {
            exit("❌ صورة الطالب student1.png غير موجودة في public/student_image/");
        }

        // إنشاء 10 مدرسين
        for ($i = 1; $i <= 10; $i++) {
            Teacher::create([
                'full_name'        => $faker->name,
                'email'            => $faker->unique()->safeEmail,
                'image'            => $"teacher1.png",
                'specialization'   => $faker->randomElement(['Math', 'Science', 'English', 'History']),
                'age'              => $faker->numberBetween(25, 60),
                'gender'           => $faker->randomElement([0, 1]),
                'password'         => Hash::make('password123'),
                'username'         => $faker->unique()->userName,
                'activation_code'  => Str::random(10),
                'is_active'        => true,
                'admin_activation' => true,
            ]);
        }

        // إنشاء 10 طلاب
        for ($i = 1; $i <= 10; $i++) {
            Student::create([
                'full_name'        => $faker->name,
                'email'            => $faker->unique()->safeEmail,
                'image'            => $studentImageUrl,
                'age'              => $faker->numberBetween(18, 30),
                'gender'           => $faker->randomElement([0, 1]),
                'free_points'      => $faker->numberBetween(0, 100),
                'paid_points'      => 1000,
                'password'         => Hash::make('password123'),
                'username'         => $faker->unique()->userName,
                'activation_code'  => Str::random(10),
                'is_active'        => true,
                'admin_activation' => true,
            ]);
        }
    }
}
