<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillsTableSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            ['title' => 'HTML'],
            ['title' => 'CSS'],
            ['title' => 'JavaScript'],
            ['title' => 'Laravel'],
            ['title' => 'React'],
            ['title' => 'Flutter'],
            ['title' => 'Python'],
            ['title' => 'TensorFlow'],
            ['title' => 'Figma'],
            ['title' => 'Photoshop'],
            ['title' => 'Ethical Hacking'],
            ['title' => 'Google Ads'],
            ['title' => 'SEO'],
            ['title' => 'AWS'],
            ['title' => 'Docker'],
            ['title' => 'SQL'],
            ['title' => 'Excel'],
            ['title' => 'Agile Methodology'],
            ['title' => 'Machine Learning'],
            ['title' => 'Illustrator'],
        ];

        DB::table('skills')->insert($skills);
    }
}
