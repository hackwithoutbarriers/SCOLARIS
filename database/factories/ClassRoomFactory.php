<?php
namespace Database\Factories;
use App\Models\{ClassRoom,AcademicYear};
use Illuminate\Database\Eloquent\Factories\Factory;
class ClassRoomFactory extends Factory {
    protected $model=ClassRoom::class;
    public function definition(): array { return ['school_id'=>\App\Models\School::factory(),'academic_year_id'=>AcademicYear::factory(),'name'=>'Class '.$this->faker->unique()->numberBetween(1,99),'grade_level'=>'primary','capacity'=>40]; }
}
