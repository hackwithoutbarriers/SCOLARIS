<?php

namespace Tests\Feature;

use App\Filament\Resources\TimetableSlotResource;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Subject;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TimetableWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_only_sees_published_timetable_slots(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = ClassRoom::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id]);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathématiques']);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        TimetableSlot::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'teacher_id' => $teacher->id,
            'class_room_id' => $class->id, 'subject_id' => $subject->id, 'day_of_week' => 1,
            'starts_at' => '08:00', 'ends_at' => '09:00', 'status' => 'draft',
        ]);
        $published = TimetableSlot::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'teacher_id' => $teacher->id,
            'class_room_id' => $class->id, 'subject_id' => $subject->id, 'day_of_week' => 2,
            'starts_at' => '08:00', 'ends_at' => '09:00', 'status' => 'published',
        ]);

        $this->actingAs($teacher);
        $this->assertCount(1, TimetableSlotResource::getEloquentQuery()->get());
        $this->assertSame($published->id, TimetableSlotResource::getEloquentQuery()->first()->id);
    }

    public function test_timetable_rejects_class_conflicts(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $class = ClassRoom::factory()->create(['school_id' => $school->id, 'academic_year_id' => $year->id]);
        $subject = Subject::create(['school_id' => $school->id, 'name' => 'Français']);
        $teacherOne = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $teacherTwo = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        TimetableSlot::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'teacher_id' => $teacherOne->id,
            'class_room_id' => $class->id, 'subject_id' => $subject->id, 'day_of_week' => 1,
            'starts_at' => '10:00', 'ends_at' => '11:00',
        ]);

        $this->expectException(ValidationException::class);
        TimetableSlot::create([
            'school_id' => $school->id, 'academic_year_id' => $year->id, 'teacher_id' => $teacherTwo->id,
            'class_room_id' => $class->id, 'subject_id' => $subject->id, 'day_of_week' => 1,
            'starts_at' => '10:30', 'ends_at' => '11:30',
        ]);
    }
}
