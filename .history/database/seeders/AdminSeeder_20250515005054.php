<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Admin::updateOrCreate(
            ['email' => 'admin@example.com'], // شرط فريد لمنع التكرار
            [
                'full_name' => 'Super Admin',
                'username' => 'admin',
                'password' => Hash::make('admin123'), // كلمة مرور مشفّرة
            ]
        );
    }
}
