<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class CourseStudentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // نفترض أن لدينا 100 طالب معرفهم من 1 إلى 100
        foreach (range(1, 100) as $studentId) {
            // كل طالب يشترك في عدد عشوائي من الكورسات (من 1 إلى 4 كورسات)
            $courseIds = $faker->randomElements(range(1, 10), rand(1, 4));

            foreach ($courseIds as $courseId) {
                DB::table('course_student')->insert([
                    'course_id'  => $courseId,
                    'student_id' => $studentId,
                    'status'     => $faker->numberBetween(0, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
