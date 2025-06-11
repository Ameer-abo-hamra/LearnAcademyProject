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

        // مسار الصورة الافتراضية
        $teacherImageName = 'teacher1.png';
        $studentImageName = 'student1.png';

        // التأكد من وجود الصور داخل المسارات الصحيحة
        if (!Storage::disk('teacher_image')->exists($teacherImageName)) {
            Storage::disk('teacher_image')->put($teacherImageName, file_get_contents(public_path('default_teacher.png')));
        }
        if (!Storage::disk('student_image')->exists($studentImageName)) {
            Storage::disk('student_image')->put($studentImageName, file_get_contents(public_path('default_student.png')));
        }

        // إنشاء 10 مدرسين
        for ($i = 1; $i <= 10; $i++) {
            Teacher::create([
                'full_name'        => $faker->name,
                'email'            => $faker->unique()->safeEmail,
                'image'            => Storage::disk('teacher_image')->url($teacherImageName),
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
                'image'            => Storage::disk('student_image')->url($studentImageName),
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
