<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        // احضار كل الكاتيجوريز
        $categories = Category::all();

        // تأكد إنو في بيانات
        if ($categories->count() == 0) {
            $this->command->info('No categories found. Please seed categories first.');
            return;
        }

        // skills مصنفة حسب الاختصاصات (categories)
        $skillsByCategory = [
            'Web Development' => ['HTML', 'CSS', 'JavaScript', 'Laravel', 'React'],
            'Mobile Development' => ['Flutter', 'React Native', 'Swift', 'Kotlin'],
            'Data Science' => ['Python', 'Pandas', 'TensorFlow', 'Machine Learning'],
            'Graphic Design' => ['Photoshop', 'Illustrator', 'InDesign'],
            'Cybersecurity' => ['Ethical Hacking', 'Network Security', 'Penetration Testing'],
            'Business' => ['Project Management', 'Agile Methodology', 'Business Analysis'],
            'Digital Marketing' => ['SEO', 'Google Ads', 'Content Marketing'],
            'Artificial Intelligence' => ['Deep Learning', 'NLP', 'Computer Vision'],
            'UI/UX Design' => ['Figma', 'Sketch', 'Adobe XD'],
            'Cloud Computing' => ['AWS', 'Azure', 'Docker'],
        ];

        foreach ($skillsByCategory as $categoryTitle => $skills) {
            $category = $categories->where('title', $categoryTitle)->first();
            if (!$category)
                continue;

            foreach ($skills as $skill) {
                DB::table('skills')->insert([
                    'title' => $skill,
                    'category_id' => $category->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

}
