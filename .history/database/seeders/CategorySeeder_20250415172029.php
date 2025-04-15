<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['title' => 'Web Development'], ['title' => 'Mobile Development'], ['title' => 'Data Science'],
            ['title' => 'Graphic Design'], ['title' => 'Cybersecurity'], ['title' => 'Business'],
            ['title' => 'Digital Marketing'], ['title' => 'Artificial Intelligence'], ['title' => 'UI/UX Design'],
            ['title' => 'Cloud Computing'], ['title' => 'Game Development'], ['title' => 'DevOps'],
            ['title' => 'Database Administration'], ['title' => 'Networking'], ['title' => 'Software Engineering'],
            ['title' => 'AR/VR'], ['title' => 'E-Commerce'], ['title' => 'Finance'], ['title' => 'Accounting'],
            ['title' => 'Content Creation'], ['title' => 'Video Editing'], ['title' => 'Animation'], ['title' => 'Education'],
            ['title' => 'Healthcare IT'], ['title' => 'Legal Tech'], ['title' => 'Sports Tech'], ['title' => 'Bioinformatics'],
            ['title' => 'Environmental Tech'], ['title' => 'Automotive Tech'], ['title' => 'Robotics'], ['title' => 'Blockchain'],
            ['title' => 'Cryptocurrency'], ['title' => 'Quantum Computing'], ['title' => 'IoT'], ['title' => 'Smart Home'],
            ['title' => 'Wearable Tech'], ['title' => 'AgriTech'], ['title' => 'Logistics'], ['title' => 'Supply Chain'],
            ['title' => 'Product Management'], ['title' => 'Sales'], ['title' => 'HR Tech'], ['title' => 'Translation'],
            ['title' => 'Language Learning'], ['title' => 'Technical Writing'], ['title' => 'Photography'], ['title' => 'Music Production'],
            ['title' => 'Cyber Law'], ['title' => 'Ethics in AI'], ['title' => 'Remote Work Tools'], ['title' => 'Virtual Collaboration'],
            ['title' => 'Presentation Skills'], ['title' => '3D Printing'], ['title' => 'Hardware Design'], ['title' => 'Embedded Systems'],
            ['title' => 'Machine Learning Ops'], ['title' => 'Security Analysis'], ['title' => 'Penetration Testing'], ['title' => 'Server Management'],
            ['title' => 'Linux Administration'], ['title' => 'Windows Server'], ['title' => 'NoSQL Databases'], ['title' => 'CRM Systems'],
            ['title' => 'ERP Systems'], ['title' => 'Accounting Software'], ['title' => 'Freelancing'], ['title' => 'Startup Management'],
            ['title' => 'Agile Coaching'], ['title' => 'Mind Mapping'], ['title' => 'Productivity'], ['title' => 'Focus Techniques'],
            ['title' => 'Stress Management'], ['title' => 'Online Security'], ['title' => 'Data Privacy'], ['title' => 'Security Compliance'],
            ['title' => 'RegTech'], ['title' => 'GovTech'], ['title' => 'EdTech'], ['title' => 'HealthTech'],
            ['title' => 'FinTech'], ['title' => 'InsurTech'], ['title' => 'Travel Tech'], ['title' => 'Hospitality Tech'],
            ['title' => 'Retail Tech'], ['title' => 'Fashion Tech'], ['title' => 'Food Tech'], ['title' => 'Space Tech'],
            ['title' => 'Smart Cities'], ['title' => 'Social Media Tools'], ['title' => 'Influencer Marketing'], ['title' => 'Branding'],
            ['title' => 'Public Speaking'], ['title' => 'Leadership'], ['title' => 'Team Building'], ['title' => 'Mentorship'],
            ['title' => 'Career Planning'], ['title' => 'Job Interview Skills'], ['title' => 'Resume Writing'], ['title' => 'LinkedIn Optimization']
        ];

        DB::table('categories')->insert($categories);
    }
}
