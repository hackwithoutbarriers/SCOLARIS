<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;
    public function definition(): array
    {
        $start = now()->startOfYear();
        return ['name' => $start->year.'-'.($start->year + 1), 'start_date' => $start, 'end_date' => $start->copy()->addYear()->subDay(), 'status' => 'active', 'is_current' => true];
    }
}
