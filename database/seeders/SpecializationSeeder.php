<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Specilization;
use App\Models\Course;
use Illuminate\Support\Facades\DB;

class SpecializationSeeder extends Seeder
{
    public function run()
    {
        // نحضر مجموعة كورسات موجودة
        $courses = Course::pluck('id')->toArray();

        if (count($courses) < 2) {
            $this->command->error('You need at least 2 courses to create a specialization.');
            return;
        }

        // نسوي مثلاً 5 تخصصات
        for ($i = 1; $i <= 5; $i++) {
            DB::beginTransaction();
            try {
                $spec = Specilization::create([
                    'title' => 'Specialization ' . $i,
                    'teacher_id' => 1, // يمكنك تغييره حسب الحاجة
                    'is_completed' => rand(0, 1),
                    'image' => 'https://via.placeholder.com/300x200.png?text=Specialization+' . $i, // صورة وهمية
                ]);

                // نختار كورسات عشوائية
                $selectedCourses = collect($courses)->random(2)->toArray(); // على الأقل 2

                // ربط الكورسات
                $spec->courses()->attach($selectedCourses);

                // ربط التصنيفات من الكورسات
                $categoryIds = Course::whereIn('id', $selectedCourses)->pluck('category_id')->unique();
                $spec->categories()->syncWithoutDetaching($categoryIds);

                // ربط المهارات من الكورسات
                $skillIds = DB::table('course_skill')
                    ->whereIn('course_id', $selectedCourses)
                    ->pluck('skill_id')
                    ->unique();

                $spec->skills()->syncWithoutDetaching($skillIds);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error('Failed to create specialization: ' . $e->getMessage());
            }
        }
    }
}
