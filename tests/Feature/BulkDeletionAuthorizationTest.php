<?php

namespace Tests\Feature;

use App\Filament\Resources\AcademicYearResource\Pages\ListAcademicYears;
use App\Filament\Resources\ClassRoomResource\Pages\ListClassRooms;
use App\Filament\Resources\ConductLabelResource\Pages\ListConductLabels;
use App\Filament\Resources\EnrollmentResource\Pages\ListEnrollments;
use App\Filament\Resources\GuardianResource\Pages\ListGuardians;
use App\Filament\Resources\MentionThresholdResource\Pages\ListMentionThresholds;
use App\Filament\Resources\ReportCardTemplateResource\Pages\ListReportCardTemplates;
use App\Filament\Resources\StudentResource\Pages\ListStudents;
use App\Filament\Resources\SubjectConfigResource\Pages\ListSubjectConfigs;
use App\Filament\Resources\SubjectResource\Pages\ListSubjects;
use App\Filament\Resources\TeacherAssignmentResource\Pages\ListTeacherAssignments;
use App\Filament\Resources\TimetableSlotResource\Pages\ListTimetableSlots;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BulkDeletionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_records_have_no_bulk_delete_action(): void
    {
        $school = School::factory()->create();
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);

        foreach ([
            ListAcademicYears::class,
            ListClassRooms::class,
            ListConductLabels::class,
            ListEnrollments::class,
            ListGuardians::class,
            ListMentionThresholds::class,
            ListReportCardTemplates::class,
            ListStudents::class,
            ListSubjectConfigs::class,
            ListSubjects::class,
            ListTeacherAssignments::class,
            ListTimetableSlots::class,
        ] as $page) {
            Livewire::actingAs($director)
                ->test($page)
                ->assertTableBulkActionDoesNotExist('delete');
        }
    }

    public function test_student_guardian_and_enrollment_records_cannot_be_deleted_by_school_staff(): void
    {
        $school = School::factory()->create();
        $year = AcademicYear::factory()->create(['school_id' => $school->id]);
        $classRoom = ClassRoom::factory()->create([
            'school_id' => $school->id,
            'academic_year_id' => $year->id,
        ]);
        $student = Student::factory()->create(['school_id' => $school->id]);
        $guardian = Guardian::factory()->create(['school_id' => $school->id]);
        $enrollment = Enrollment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'class_room_id' => $classRoom->id,
            'academic_year_id' => $year->id,
            'enrollment_date' => today(),
            'status' => 'active',
        ]);

        foreach (['director', 'secretary'] as $role) {
            $staff = User::factory()->create(['school_id' => $school->id, 'role' => $role]);

            foreach ([$student, $guardian, $enrollment] as $record) {
                $this->assertFalse($staff->can('delete', $record));
            }
        }
    }
}
