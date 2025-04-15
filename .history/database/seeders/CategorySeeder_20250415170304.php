<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
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
        ];

        DB::table('categories')->insert($categories);
    }
}
