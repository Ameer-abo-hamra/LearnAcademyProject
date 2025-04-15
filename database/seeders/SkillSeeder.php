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

        if ($categories->count() == 0) {
            $this->command->info('No categories found. Please seed categories first.');
            return;
        }

        $skillsByCategory = [
            'Web Development' => ['HTML', 'CSS', 'JavaScript', 'PHP', 'Laravel', 'Vue.js', 'React', 'Angular', 'TypeScript', 'Next.js'],
            'Mobile Development' => ['Flutter', 'React Native', 'Swift', 'Kotlin', 'Java', 'Dart', 'Objective-C'],
            'Data Science' => ['Python', 'R', 'NumPy', 'Pandas', 'Matplotlib', 'SciPy', 'Scikit-learn', 'TensorFlow', 'PyTorch'],
            'Graphic Design' => ['Photoshop', 'Illustrator', 'InDesign', 'CorelDRAW', 'Affinity Designer'],
            'Cybersecurity' => ['Ethical Hacking', 'Network Security', 'Penetration Testing', 'Wireshark', 'Metasploit', 'Cryptography'],
            'Business' => ['Project Management', 'Business Analysis', 'Risk Management', 'Scrum', 'Kanban'],
            'Digital Marketing' => ['SEO', 'SEM', 'Content Marketing', 'Email Marketing', 'Google Ads', 'Facebook Ads'],
            'Artificial Intelligence' => ['Deep Learning', 'NLP', 'Computer Vision', 'Reinforcement Learning', 'GANs'],
            'UI/UX Design' => ['Figma', 'Sketch', 'Adobe XD', 'InVision', 'Design Thinking'],
            'Cloud Computing' => ['AWS', 'Azure', 'Google Cloud', 'Docker', 'Kubernetes', 'Terraform'],
            'DevOps' => ['CI/CD', 'Jenkins', 'GitLab CI', 'Ansible', 'Chef', 'Puppet'],
            'Game Development' => ['Unity', 'Unreal Engine', 'C#', 'Game Design', '2D Animation', '3D Modeling'],
            'Finance' => ['Financial Analysis', 'Budgeting', 'Forecasting', 'Accounting', 'Excel'],
            'Healthcare' => ['Medical Terminology', 'EHR Systems', 'Nursing', 'Pharmacology', 'Public Health'],
            'Education' => ['Curriculum Design', 'eLearning', 'Instructional Design', 'Teaching Methods'],
            'Photography' => ['Portrait Photography', 'Landscape Photography', 'Lightroom', 'DSLR'],
            'Writing' => ['Creative Writing', 'Copywriting', 'Technical Writing', 'Editing', 'Proofreading'],
            'Legal' => ['Contract Law', 'Corporate Law', 'Legal Research', 'Compliance'],
            'Engineering' => ['AutoCAD', 'SolidWorks', 'MATLAB', 'Electrical Design', 'Mechanical Design'],
            'Real Estate' => ['Property Management', 'Real Estate Law', 'Negotiation', 'Market Analysis'],
            'Logistics' => ['Supply Chain', 'Inventory Management', 'Fleet Management', 'Warehousing'],
            'Human Resources' => ['Recruiting', 'Onboarding', 'Payroll', 'Employee Relations', 'HRIS'],
            'Language Learning' => ['English Grammar', 'Spanish Vocabulary', 'French Conversation', 'Japanese Writing'],
            'Music' => ['Guitar', 'Piano', 'Music Theory', 'Audio Editing', 'Songwriting'],
            'Culinary Arts' => ['Baking', 'Food Safety', 'Culinary Techniques', 'Menu Planning'],
            'Architecture' => ['Revit', '3ds Max', 'Building Codes', 'Urban Planning'],
            'Animation' => ['2D Animation', '3D Animation', 'Motion Graphics', 'Storyboard Design'],
            'Blockchain' => ['Smart Contracts', 'Ethereum', 'Solidity', 'Bitcoin', 'NFTs'],
            'Sales' => ['Lead Generation', 'Sales Strategy', 'CRM Tools', 'Cold Calling'],
            'Customer Service' => ['Conflict Resolution', 'CRM Systems', 'Communication Skills'],
            'Science' => ['Biology', 'Chemistry', 'Physics', 'Laboratory Skills'],
            'Mathematics' => ['Algebra', 'Calculus', 'Statistics', 'Probability'],
            'Economics' => ['Microeconomics', 'Macroeconomics', 'Game Theory'],
            'Fitness' => ['Yoga', 'Weight Training', 'Nutrition', 'Personal Training'],
            'Fashion' => ['Fashion Design', 'Textiles', 'Sewing', 'Trend Analysis'],
            'Automotive' => ['Car Diagnostics', 'Engine Repair', 'Vehicle Maintenance'],
            'Agriculture' => ['Crop Management', 'Irrigation Systems', 'Soil Science'],
            'Environmental Science' => ['Sustainability', 'Ecology', 'Climate Change'],
            'Event Planning' => ['Wedding Planning', 'Conference Management', 'Budgeting'],
            'Hospitality' => ['Hotel Management', 'Housekeeping', 'Customer Relations'],
            'Construction' => ['Blueprint Reading', 'Masonry', 'Carpentry', 'Site Safety'],
            'Media' => ['Video Editing', 'Audio Production', 'Broadcasting'],
            'Public Speaking' => ['Speech Writing', 'Presentation Design', 'Confidence Building'],
            'Research' => ['Literature Review', 'Data Collection', 'Field Work'],
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
