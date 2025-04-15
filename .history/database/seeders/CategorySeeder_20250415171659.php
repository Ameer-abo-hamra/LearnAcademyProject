<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['title' => 'Web Development'],
            ['title' => 'Mobile Development'],
            ['title' => 'Data Science'],
            ['title' => 'Graphic Design'],
            ['title' => 'Cybersecurity'],
            ['title' => 'Business'],
            ['title' => 'Digital Marketing'],
            ['title' => 'Artificial Intelligence'],
            ['title' => 'UI/UX Design'],
            ['title' => 'Cloud Computing'],
            ['title' => 'Game Development'],
            ['title' => 'DevOps'],
            ['title' => 'Database Administration'],
            ['title' => 'Networking'],
            ['title' => 'Software Engineering'],
            ['title' => 'Augmented & Virtual Reality'],
            ['title' => 'E-Commerce'],
            ['title' => 'Finance & Accounting'],
            ['title' => 'Content Creation'],
            ['title' => 'Video Editing'],
        ];

        DB::table('categories')->insert($categories);
    }
}
