<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();

        if ($categories->count() === 0) {
            $this->command->info('No categories found. Please seed categories first.');
            return;
        }

        $skillsByCategory = [
            'Web Development' => ['HTML', 'CSS', 'JavaScript', 'Laravel', 'Vue.js', 'React', 'Tailwind CSS', 'Node.js', 'Next.js'],
            'Mobile Development' => ['Flutter', 'React Native', 'Swift', 'Kotlin', 'Java (Android)', 'Dart'],
            'Data Science' => ['Python', 'Pandas', 'NumPy', 'R', 'TensorFlow', 'Jupyter', 'Scikit-learn', 'Matplotlib'],
            'Graphic Design' => ['Photoshop', 'Illustrator', 'InDesign', 'Canva', 'Affinity Designer'],
            'Cybersecurity' => ['Ethical Hacking', 'Penetration Testing', 'Kali Linux', 'Wireshark', 'Network Security', 'Cryptography'],
            'Business' => ['Project Management', 'Scrum', 'Agile', 'Business Analysis', 'Strategic Planning', 'OKRs'],
            'Digital Marketing' => ['SEO', 'Google Ads', 'Email Marketing', 'Content Strategy', 'Social Media Marketing', 'Google Analytics'],
            'Artificial Intelligence' => ['Deep Learning', 'Computer Vision', 'Natural Language Processing', 'PyTorch', 'Keras'],
            'UI/UX Design' => ['Figma', 'Sketch', 'Adobe XD', 'User Research', 'Wireframing', 'Prototyping'],
            'Cloud Computing' => ['AWS', 'Azure', 'Google Cloud', 'Docker', 'Kubernetes', 'CI/CD'],
            'Game Development' => ['Unity', 'Unreal Engine', 'C#', 'Game Design', '2D/3D Modeling'],
            'DevOps' => ['Jenkins', 'Ansible', 'Terraform', 'Monitoring (Prometheus)', 'CI/CD Pipelines'],
            'Database Administration' => ['MySQL', 'PostgreSQL', 'MongoDB', 'Oracle DB', 'Redis', 'Database Tuning'],
            'Networking' => ['CCNA', 'Cisco Routing', 'Firewall Configurations', 'LAN/WAN', 'Network Protocols'],
            'Software Engineering' => ['Design Patterns', 'Version Control (Git)', 'OOP', 'SDLC', 'Testing & Debugging'],
            'Augmented & Virtual Reality' => ['ARKit', 'ARCore', 'Oculus SDK', 'Unity XR Toolkit', '3D Modeling'],
            'E-Commerce' => ['Shopify', 'Magento', 'WooCommerce', 'Payment Integration', 'Product Listing'],
            'Finance & Accounting' => ['QuickBooks', 'Financial Reporting', 'Budgeting', 'Excel', 'Bookkeeping'],
            'Content Creation' => ['Copywriting', 'Blog Writing', 'Podcast Production', 'Content Planning', 'Storytelling'],
            'Video Editing' => ['Adobe Premiere Pro', 'Final Cut Pro', 'DaVinci Resolve', 'After Effects', 'Color Grading'],
        ];

        foreach ($skillsByCategory as $categoryTitle => $skills) {
            $category = $categories->where('title', $categoryTitle)->first();
            if (!$category) continue;

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
