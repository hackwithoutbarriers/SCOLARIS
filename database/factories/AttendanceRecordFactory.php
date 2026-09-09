<?php
namespace Database\Factories;
use App\Models\{AttendanceRecord,AttendanceSession,Student};
use Illuminate\Database\Eloquent\Factories\Factory;
class AttendanceRecordFactory extends Factory {
    protected $model=AttendanceRecord::class;
    public function definition(): array { return ['school_id'=>\App\Models\School::factory(),'attendance_session_id'=>AttendanceSession::factory(),'student_id'=>Student::factory(),'status'=>$this->faker->randomElement(['present','absent','late','excused']),'marked_at'=>now(),'client_id'=>(string)\Illuminate\Support\Str::uuid()]; }
}
