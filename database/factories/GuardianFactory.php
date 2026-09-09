<?php

namespace Database\Factories;

use App\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuardianFactory extends Factory
{
    protected $model = Guardian::class;
    public function definition(): array
    {
        $first = fake()->firstName(); $last = fake()->lastName();
        return ['first_name' => $first, 'last_name' => $last, 'name' => $first.' '.$last, 'relationship' => 'Parent', 'phone' => fake()->phoneNumber(), 'email' => fake()->safeEmail(), 'active' => true];
    }
}
