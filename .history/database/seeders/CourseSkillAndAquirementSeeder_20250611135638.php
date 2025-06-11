<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class CourseSkillAndAquirementSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // لكل كورس، نربط من 2 إلى 5 مهارات كـ skills
        foreach (range(1, 10) as $courseId) {
            $skillIds = $faker->randomElements(range(1, 100), rand(2, 5));

            foreach ($skillIds as $skillId) {
                DB::table('course_skill')->insert([
                    'course_id' => $courseId,
                    'skill_id' => $skillId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // لكل كورس، نربط من 1 إلى 3 مهارات كـ aquirements
        foreach (range(1, 10) as $courseId) {
            $aquirementIds = $faker->randomElements(range(1, 100), rand(1, 3));

            foreach ($aquirementIds as $skillId) {
                DB::table('course_aquirement')->insert([
                    'course_id' => $courseId,
                    'skill_id' => $skillId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
