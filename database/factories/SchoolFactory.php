<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolFactory extends Factory
{
    protected $model = School::class;
    public function definition(): array
    {
        $name = fake()->unique()->company().' School';
        return ['name' => $name, 'code' => fake()->unique()->bothify('SCH-###'), 'slug' => str()->slug($name), 'country' => 'Togo', 'active' => true, 'timezone' => 'Africa/Lome'];
    }
}
