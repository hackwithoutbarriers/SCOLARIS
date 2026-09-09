<?php
namespace Database\Factories;
use App\Models\{AttendanceSession,ClassRoom,User};
use Illuminate\Database\Eloquent\Factories\Factory;
class AttendanceSessionFactory extends Factory {
    protected $model=AttendanceSession::class;
    public function definition(): array { return ['school_id'=>\App\Models\School::factory(),'class_room_id'=>ClassRoom::factory(),'teacher_id'=>User::factory(),'session_date'=>now()->toDateString(),'status'=>'open']; }
}
