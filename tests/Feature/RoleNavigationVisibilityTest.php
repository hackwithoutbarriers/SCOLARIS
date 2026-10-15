<?php

namespace Tests\Feature;

use App\Filament\Pages\AbsenceWorklist;
use App\Filament\Pages\CollectionCenter;
use App\Filament\Pages\GradeEntry;
use App\Filament\Pages\MissingGrades;
use App\Filament\Pages\SchoolOnboarding;
use App\Filament\Resources\AcademicYearResource;
use App\Filament\Resources\AssessmentResource;
use App\Filament\Resources\CertificateResource;
use App\Filament\Resources\ClassRoomResource;
use App\Filament\Resources\ConductLabelResource;
use App\Filament\Resources\EnrollmentResource;
use App\Filament\Resources\ExpenseResource;
use App\Filament\Resources\FeeStructureResource;
use App\Filament\Resources\GradeResource;
use App\Filament\Resources\GuardianResource;
use App\Filament\Resources\MentionThresholdResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\RegistrationRequestResource;
use App\Filament\Resources\ReportCardResource;
use App\Filament\Resources\ReportCardTemplateResource;
use App\Filament\Resources\SchoolResource;
use App\Filament\Resources\SchoolSubscriptionResource;
use App\Filament\Resources\StudentResource;
use App\Filament\Resources\SubjectConfigResource;
use App\Filament\Resources\SubjectResource;
use App\Filament\Resources\TeacherAssignmentResource;
use App\Filament\Resources\TermResource;
use App\Filament\Resources\TimetableSlotResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\AcademicOverview;
use App\Filament\Widgets\AttendanceOverview;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleNavigationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_is_scoped_to_the_role_and_direct_page_access_is_denied(): void
    {
        $school = School::factory()->create();
        $accountant = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);

        $this->actingAs($accountant);
        $this->assertFalse(GradeEntry::shouldRegisterNavigation());
        $this->assertFalse(GradeResource::canViewAny());
        $this->assertFalse(AssessmentResource::canViewAny());
        $this->assertTrue(CollectionCenter::shouldRegisterNavigation());
        $this->get('/admin/grade-entry')->assertForbidden();

        $this->actingAs($teacher);
        $this->assertTrue(GradeEntry::shouldRegisterNavigation());
        $this->assertTrue(MissingGrades::shouldRegisterNavigation());
        $this->assertFalse(CollectionCenter::shouldRegisterNavigation());
        $this->get('/admin/collection-center')->assertForbidden();
    }

    public function test_resources_without_role_specific_workflows_are_hidden_from_staff_navigation(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
        $accountant = User::factory()->create(['school_id' => $school->id, 'role' => 'accountant']);
        $secretary = User::factory()->create(['school_id' => $school->id, 'role' => 'secretary']);
        $director = User::factory()->create(['school_id' => $school->id, 'role' => 'director']);
        $superAdmin = User::factory()->create(['school_id' => null, 'role' => 'super_admin']);

        $resources = [
            AcademicYearResource::class,
            ClassRoomResource::class,
            ConductLabelResource::class,
            FeeStructureResource::class,
            MentionThresholdResource::class,
            ReportCardTemplateResource::class,
            SubjectConfigResource::class,
            SubjectResource::class,
            TeacherAssignmentResource::class,
            TermResource::class,
        ];

        foreach ([$teacher, $accountant, $secretary] as $staff) {
            $this->actingAs($staff);
            foreach ($resources as $resource) {
                $this->assertFalse($resource::canViewAny(), $resource.' should be hidden for staff');
            }

            $this->actingAs($secretary);
            $this->assertTrue(CertificateResource::canViewAny());
            $this->assertFalse(SchoolResource::canViewAny());
            $this->assertFalse(SchoolSubscriptionResource::canViewAny());
            $this->assertFalse(UserResource::canViewAny());
        }

        $this->actingAs($director);
        foreach ($resources as $resource) {
            $this->assertTrue($resource::canViewAny(), $resource.' should be visible to directors');
        }
        $this->assertTrue(TimetableSlotResource::canViewAny());
        $this->assertTrue(TimetableSlotResource::canCreate());
        $this->assertTrue(RegistrationRequestResource::canViewAny());

        Livewire::test(SchoolOnboarding::class)
            ->assertSee('/admin/registration-requests');

        $this->actingAs($superAdmin);
        foreach ($resources as $resource) {
            $this->assertFalse($resource::canViewAny(), $resource.' requires an intervention for super admins');
        }
        $this->assertTrue(SchoolResource::canViewAny());
        $this->assertTrue(SchoolSubscriptionResource::canViewAny());
        $this->assertFalse(UserResource::canViewAny());

        $this->withSession([
            'scolaris_intervention.school_id' => $school->id,
            'scolaris_intervention.expires_at' => now()->addHour()->timestamp,
        ])->actingAs($superAdmin);
        foreach ($resources as $resource) {
            $this->assertTrue($resource::canViewAny(), $resource.' should be visible during the selected-school intervention');
        }
        $this->assertTrue(UserResource::canViewAny());

        $this->actingAs($teacher);
        $this->assertTrue(TimetableSlotResource::canViewAny());
        $this->assertFalse(TimetableSlotResource::canCreate());

        $this->actingAs($secretary);
        $this->assertTrue(StudentResource::canViewAny());
        $this->assertTrue(EnrollmentResource::canViewAny());
        $this->assertTrue(CertificateResource::canViewAny());
        $this->assertTrue(TimetableSlotResource::canViewAny());
        $this->assertSame('Inscrire un élève', StudentResource::getCreateButtonLabel());
        $this->assertSame('Demander un certificat', CertificateResource::getCreateButtonLabel());
    }

    public function test_report_cards_remain_visible_to_teachers(): void
    {
        $school = School::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);

        $this->actingAs($teacher);

        $this->assertTrue(ReportCardResource::canViewAny());
    }

    public function test_censeur_has_read_only_academic_access_and_school_wide_attendance_access(): void
    {
        $school = School::factory()->create();
        $censeur = User::factory()->create(['school_id' => $school->id, 'role' => 'censeur']);

        $this->actingAs($censeur);

        $this->assertTrue(GradeResource::canViewAny());
        $this->assertTrue(ReportCardResource::canViewAny());
        $this->assertTrue(MissingGrades::canAccess());
        $this->assertTrue(AbsenceWorklist::canAccess());
        $this->assertTrue(AttendanceOverview::canView());
        $this->assertTrue(AcademicOverview::canView());
        $this->assertFalse(PaymentResource::canViewAny());
        $this->get('/admin/grades')->assertOk();
        $this->get('/admin/grades/create')->assertForbidden();
        $this->get('/admin/report-cards')->assertOk();
        $this->get('/admin/missing-grades')->assertOk();
        $this->get('/admin/absence-worklist')->assertOk();
        $this->get('/admin/payments')->assertForbidden();
    }

    public function test_high_risk_resource_visibility_matches_the_role_matrix(): void
    {
        $school = School::factory()->create();
        $expected = [
            'director' => [true, true, true, true, true, true, true, true],
            'teacher' => [false, true, true, false, true, true, false, false],
            'accountant' => [true, false, false, false, false, false, false, false],
            'censeur' => [false, true, true, true, false, true, true, true],
            'secretary' => [false, false, false, true, false, false, false, false],
        ];

        foreach ($expected as $role => $rights) {
            $user = User::factory()->create(['school_id' => $school->id, 'role' => $role]);
            $this->actingAs($user);

            $actual = [
                PaymentResource::canViewAny(),
                GradeResource::canViewAny(),
                ReportCardResource::canViewAny(),
                AbsenceWorklist::canAccess(),
                GradeEntry::canAccess(),
                MissingGrades::canAccess(),
                AcademicOverview::canView(),
                AttendanceOverview::canView(),
            ];

            $this->assertSame($rights, $actual, "Access matrix mismatch for {$role}.");
        }
    }

    public function test_resource_visibility_matrix_covers_each_role_and_school_intervention(): void
    {
        $school = School::factory()->create();
        $resources = [
            AcademicYearResource::class,
            AssessmentResource::class,
            CertificateResource::class,
            ClassRoomResource::class,
            ConductLabelResource::class,
            EnrollmentResource::class,
            ExpenseResource::class,
            FeeStructureResource::class,
            GradeResource::class,
            GuardianResource::class,
            MentionThresholdResource::class,
            PaymentResource::class,
            RegistrationRequestResource::class,
            ReportCardResource::class,
            ReportCardTemplateResource::class,
            SchoolResource::class,
            SchoolSubscriptionResource::class,
            StudentResource::class,
            SubjectConfigResource::class,
            SubjectResource::class,
            TeacherAssignmentResource::class,
            TermResource::class,
            TimetableSlotResource::class,
            UserResource::class,
        ];

        $expectedByRole = [
            'director' => [
                AcademicYearResource::class, AssessmentResource::class, CertificateResource::class,
                ClassRoomResource::class, ConductLabelResource::class, EnrollmentResource::class,
                ExpenseResource::class, FeeStructureResource::class, GradeResource::class,
                GuardianResource::class, MentionThresholdResource::class, PaymentResource::class,
                RegistrationRequestResource::class, ReportCardResource::class, ReportCardTemplateResource::class,
                StudentResource::class, SubjectConfigResource::class, SubjectResource::class,
                TeacherAssignmentResource::class, TermResource::class, TimetableSlotResource::class,
            ],
            'teacher' => [
                AssessmentResource::class, GradeResource::class, ReportCardResource::class,
                TimetableSlotResource::class,
            ],
            'accountant' => [
                ExpenseResource::class, PaymentResource::class, TimetableSlotResource::class,
            ],
            'censeur' => [
                AssessmentResource::class, GradeResource::class, ReportCardResource::class,
                TimetableSlotResource::class,
            ],
            'secretary' => [
                CertificateResource::class, EnrollmentResource::class, GuardianResource::class,
                StudentResource::class, TimetableSlotResource::class,
            ],
        ];

        foreach ($expectedByRole as $role => $visibleResources) {
            $this->actingAs(User::factory()->create(['school_id' => $school->id, 'role' => $role]));

            foreach ($resources as $resource) {
                $this->assertSame(
                    in_array($resource, $visibleResources, true),
                    $resource::canViewAny(),
                    "{$resource} visibility mismatch for {$role}.",
                );
            }
        }

        $superAdmin = User::factory()->create(['school_id' => null, 'role' => 'super_admin']);
        $this->actingAs($superAdmin);
        $platformResources = [RegistrationRequestResource::class, SchoolResource::class, SchoolSubscriptionResource::class];
        foreach ($resources as $resource) {
            $this->assertSame(
                in_array($resource, $platformResources, true),
                $resource::canViewAny(),
                "{$resource} visibility mismatch for super_admin without intervention.",
            );
        }

        $this->withSession([
            'scolaris_intervention.school_id' => $school->id,
            'scolaris_intervention.expires_at' => now()->addHour()->timestamp,
        ])->actingAs($superAdmin);
        foreach ($resources as $resource) {
            $this->assertTrue($resource::canViewAny(), "{$resource} should be available during intervention.");
        }
    }
}
