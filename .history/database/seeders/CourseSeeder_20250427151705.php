<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Skill;
use App\Models\Teacher;
use App\Models\Category;
use App\Models\CourseAttachments;

class CourseSeeder extends Seeder
{
    public function run()
    {
        // نحضر بيانات موجودة أو ننشئ جديدة
        $teacher = Teacher::first() ?? Teacher::factory()->create();
        $category = Category::first() ?? Category::factory()->create();
        $skills = Skill::pluck('id')->toArray();

        if (empty($skills)) {
            $skills = Skill::factory()->count(5)->create()->pluck('id')->toArray();
        }

        // نكرر العملية 10 مرات
        for ($i = 1; $i <= 10; $i++) {
            $course = Course::create([
                'name' => 'Course ' . $i,
                'description' => 'Description for course ' . $i,
                'image' => 'https://via.placeholder.com/300x200.png?text=Course+' . $i, // صورة وهمية جاهزة
                'level' => rand(0, 2), // 0 مبتدئ - 1 متوسط - 2 متقدم
                'teacher_id' => $teacher->id,
                'category_id' => $category->id,
                'point_to_enroll' => rand(0, 10),
                'points_earned' => rand(0, 10),
                ("status")->rand(0,)

            ]);

            // ربط مهارات ومتطلبات عشوائية
            shuffle($skills);
            $randomSkills = array_slice($skills, 0, 3);
            $randomAquirements = array_slice($skills, 3, 2);

            $course->skills()->sync($randomSkills);
            $course->aquirements()->sync($randomAquirements);

            // إنشاء مرفقات نصية
            CourseAttachments::create([
                'course_id' => $course->id,
                'file_path' => null,
                'text' => 'Welcome to course ' . $i,
            ]);

            CourseAttachments::create([
                'course_id' => $course->id,
                'file_path' => null,
                'text' => 'Please read the course instructions carefully.',
            ]);
        }
    }
}
