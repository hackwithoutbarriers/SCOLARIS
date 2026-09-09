<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;
    public function definition(): array
    {
        $number = 'STU-'.fake()->unique()->numerify('#####');
        return ['student_number' => $number, 'admission_number' => $number, 'first_name' => fake()->firstName(), 'last_name' => fake()->lastName(), 'date_of_birth' => fake()->date(), 'status' => 'active', 'active' => true];
    }
}
