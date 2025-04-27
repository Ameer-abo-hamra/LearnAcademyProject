<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Skill;
use App\Models\Teacher;
use App\Models\Category;
use App\Models\CourseAttachments;
use Illuminate\Support\Facades\Storage;

class CourseSeeder extends Seeder
{
    public function run()
    {
        // نجيب بيانات موجودة أو نصنع داتا وهمية
        $teacher = Teacher::first() ?? Teacher::factory()->create();
        $category = Category::first() ?? Category::factory()->create();
        $skills = Skill::pluck('id')->toArray();

        if (empty($skills)) {
            $skills = Skill::factory()->count(5)->create()->pluck('id')->toArray();
        }

        // إنشاء كورس
        $course = Course::create([
            'name' => 'Introduction to Programming',
            'description' => 'This course covers the basics of programming in an easy and simple way.',
            'image' => '', // مؤقتًا
            'level' => 1, // متوسط
            'teacher_id' => $teacher->id,
            'category_id' => $category->id,
            'point_to_enroll' => 5,
            'points_earned' => 5,
        ]);

        // رفع صورة وهمية (اختياري)
        // $fakeImage = 'course_image/default.png';
        // if (!Storage::disk('course_image')->exists('default.png')) {
        //     Storage::disk('course_image')->put('default.png', file_get_contents(public_path('default.png')));
        // }
        // $course->image = assetFromDisk('course_image', 'default.png');
        // $course->save();

        // ربط المهارات والمتطلبات
        $randomSkills = array_slice($skills, 0, 3);
        $randomAquirements = array_slice($skills, 3, 2);

        $course->skills()->sync($randomSkills);
        $course->aquirements()->sync($randomAquirements);

        // إنشاء مرفقات
        CourseAttachments::create([
            'course_id' => $course->id,
            'file_path' => null,
            'text' => 'Welcome to the course!',
        ]);

        CourseAttachments::create([
            'course_id' => $course->id,
            'file_path' => null,
            'text' => 'Remember to check the syllabus.',
        ]);
    }
}
