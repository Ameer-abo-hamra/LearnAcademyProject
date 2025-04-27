<?php

namespace Database\Factories;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition()
    {
        return [
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'image' => '', // تقدر تغير المسار لو عندك صورة وهمية
            'specialization' => $this->faker->randomElement(['Math', 'Physics', 'Computer Science', 'Biology']),
            'age' => $this->faker->numberBetween(25, 60),
            'gender' => $this->faker->randomElement([0, 1]), // 0 = ذكر, 1 = أنثى مثلا
            'password' => bcrypt('password'), // أو تقدر تخليه ثابت
            'username' => $this->faker->unique()->userName(),
            'activation_code' => Str::random(10),
            'is_active' => $this->faker->boolean(),
            'admin_activation' => $this->faker->boolean(),
        ];
    }
}
