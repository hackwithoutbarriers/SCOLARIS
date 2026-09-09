<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\School;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\Enrollment;
use App\Models\TeacherAssignment;
use App\Models\Term;
use App\Models\SubjectConfig;
use App\Models\Assessment;
use App\Models\Grade;
use App\Models\AttendanceSession;
use App\Models\AttendanceRecord;
use App\Models\ReportCardTemplate;
use App\Models\FeeStructure;
use App\Models\Fee;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Receipt;
use App\Models\ReportCard;
use App\Models\ReportCardVersion;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::firstOrCreate(['code' => 'DEMO'], ['name' => 'Scolaris Demo School', 'slug' => 'demo-school', 'country' => 'Togo', 'active' => true]);
        $year = AcademicYear::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id, 'name' => '2026-2027'],
            ['start_date' => '2026-09-01', 'end_date' => '2027-08-31', 'status' => 'active', 'is_current' => true],
        );
        $admin = User::updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Super Admin', 'first_name' => 'Super', 'last_name' => 'Admin', 'school_id' => null, 'role' => 'super_admin',
            'password' => Hash::make('password'), 'is_active' => true, 'email_verified_at' => now(),
        ]);
        User::updateOrCreate(['email' => 'director@example.com'], [
            'name' => 'Demo Director', 'first_name' => 'Demo', 'last_name' => 'Director',
            'school_id' => $school->id, 'role' => 'director', 'password' => Hash::make('password'), 'is_active' => true, 'email_verified_at' => now(),
        ]);
        $teacher = User::updateOrCreate(['email' => 'teacher@example.com'], [
            'name' => 'Demo Teacher', 'first_name' => 'Demo', 'last_name' => 'Teacher', 'school_id' => $school->id, 'role' => 'teacher',
            'password' => Hash::make('password'), 'is_active' => true, 'email_verified_at' => now(),
        ]);
        $accountant = User::updateOrCreate(['email' => 'accountant@example.com'], [
            'name' => 'Demo Accountant', 'first_name' => 'Demo', 'last_name' => 'Accountant',
            'school_id' => $school->id, 'role' => 'accountant', 'password' => Hash::make('password'), 'is_active' => true, 'email_verified_at' => now(),
        ]);
        $class = ClassRoom::withoutGlobalScopes()->firstOrCreate(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Grade 1A'], ['grade_level' => 'Grade 1', 'capacity' => 30]);
        $subject = Subject::withoutGlobalScopes()->firstOrCreate(['school_id' => $school->id, 'name' => 'Mathematics'], ['code' => 'MATH']);
        $french = Subject::withoutGlobalScopes()->firstOrCreate(['school_id' => $school->id, 'name' => 'French'], ['code' => 'FR']);
        TeacherAssignment::withoutGlobalScopes()->firstOrCreate([
            'school_id' => $school->id, 'teacher_id' => $teacher->id, 'class_room_id' => $class->id,
            'subject_id' => $subject->id, 'academic_year_id' => $year->id,
        ]);
        $student = Student::withoutGlobalScopes()->firstOrCreate(['school_id' => $school->id, 'student_number' => 'STU-00001'], ['admission_number' => 'STU-00001', 'first_name' => 'Ada', 'last_name' => 'Lovelace', 'active' => true, 'status' => 'active']);
        $guardian = Guardian::withoutGlobalScopes()->firstOrCreate(['school_id' => $school->id, 'phone' => '+10000000000'], ['name' => 'Grace Lovelace', 'relationship' => 'Parent']);
        $student->guardians()->syncWithoutDetaching([$guardian->id => ['is_primary' => true]]);
        Enrollment::withoutGlobalScopes()->firstOrCreate(['school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $year->id], ['class_room_id' => $class->id, 'enrollment_date' => now()->toDateString(), 'enrolled_at' => now()->toDateString(), 'status' => 'active']);

        foreach ([
            ['Awa', 'Kossi', 'STU-00002', '+22890000002'],
            ['Koffi', 'Mensah', 'STU-00003', '+22890000003'],
            ['Ama', 'Doe', 'STU-00004', '+22890000004'],
            ['Yao', 'Agbenyega', 'STU-00005', '+22890000005'],
            ['Esi', 'Adjei', 'STU-00006', '+22890000006'],
        ] as [$firstName, $lastName, $number, $phone]) {
            $demoStudent = Student::withoutGlobalScopes()->firstOrCreate(
                ['school_id' => $school->id, 'student_number' => $number],
                ['admission_number' => $number, 'first_name' => $firstName, 'last_name' => $lastName, 'date_of_birth' => '2016-03-15', 'active' => true, 'status' => 'active'],
            );
            $demoGuardian = Guardian::withoutGlobalScopes()->firstOrCreate(
                ['school_id' => $school->id, 'phone' => $phone],
                ['name' => $lastName.' Parent', 'relationship' => 'Parent'],
            );
            $demoStudent->guardians()->syncWithoutDetaching([$demoGuardian->id => ['is_primary' => true]]);
            Enrollment::withoutGlobalScopes()->firstOrCreate(
                ['school_id' => $school->id, 'student_id' => $demoStudent->id, 'academic_year_id' => $year->id],
                ['class_room_id' => $class->id, 'enrollment_date' => '2026-09-01', 'enrolled_at' => '2026-09-01', 'status' => 'active'],
            );
        }

        $term = Term::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Term 1'],
            ['starts_at' => '2026-09-01', 'ends_at' => '2026-12-20', 'sort_order' => 1],
        );
        foreach ([$subject, $french] as $seedSubject) {
            $config = SubjectConfig::withoutGlobalScopes()->firstOrCreate(
                ['school_id' => $school->id, 'subject_id' => $seedSubject->id, 'academic_year_id' => $year->id, 'class_room_id' => $class->id],
                ['version' => 1, 'grading_method' => 'weighted_average', 'passing_score' => 10, 'max_score' => 20, 'weight' => 1, 'active' => true],
            );
            $assessment = Assessment::withoutGlobalScopes()->firstOrCreate(
                ['school_id' => $school->id, 'subject_config_id' => $config->id, 'term_id' => $term->id, 'title' => 'Devoir 1'],
                ['teacher_id' => $teacher->id, 'assessment_date' => '2026-10-15', 'max_score' => 20, 'weight' => 1, 'status' => 'published'],
            );
            foreach (Student::withoutGlobalScopes()->where('school_id', $school->id)->limit(6)->get() as $seedStudent) {
                Grade::withoutGlobalScopes()->updateOrCreate(
                    ['school_id' => $school->id, 'assessment_id' => $assessment->id, 'student_id' => $seedStudent->id],
                    ['graded_by' => $teacher->id, 'score' => 12 + ($seedStudent->id % 7), 'validated_at' => now()],
                );
            }
        }

        $session = AttendanceSession::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id, 'class_room_id' => $class->id, 'session_date' => '2026-09-08'],
            ['academic_year_id' => $year->id, 'teacher_id' => $teacher->id, 'started_at' => '2026-09-08 07:30:00', 'validated_at' => '2026-09-08 08:00:00', 'status' => 'VALIDATED'],
        );
        foreach (Student::withoutGlobalScopes()->where('school_id', $school->id)->limit(6)->get() as $index => $seedStudent) {
            AttendanceRecord::withoutGlobalScopes()->updateOrCreate(
                ['school_id' => $school->id, 'attendance_session_id' => $session->id, 'student_id' => $seedStudent->id],
                ['status' => $index === 2 ? 'ABSENT' : 'PRESENT', 'marked_at' => now(), 'marked_by' => $teacher->id],
            );
        }

        $template = ReportCardTemplate::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id, 'name' => 'Bulletin standard'],
            ['version' => 1, 'status' => 'ACTIVE', 'orientation' => 'portrait', 'schema' => ReportCardTemplate::defaultSchema(), 'active' => true],
        );
        if (!ReportCard::withoutGlobalScopes()->where('school_id', $school->id)->where('student_id', $student->id)->where('term_id', $term->id)->exists()) {
            $reportCard = Model::withoutEvents(fn () => ReportCard::create([
                'school_id' => $school->id,
                'student_id' => $student->id,
                'academic_year_id' => $year->id,
                'term_id' => $term->id,
                'template_id' => $template->id,
                'version' => 1,
                'status' => 'draft',
                'data' => app(\App\Services\GradeCalculationService::class)->calculate($student, $term),
                'generated_at' => now(),
            ]));
            Model::withoutEvents(fn () => ReportCardVersion::create([
                'school_id' => $school->id,
                'report_card_id' => $reportCard->id,
                'version' => 1,
                'status' => 'draft',
                'data' => $reportCard->data,
            ]));
        }

        $feeStructure = FeeStructure::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id, 'academic_year_id' => $year->id, 'class_room_id' => $class->id, 'name' => 'Frais scolaires 2026-2027'],
            ['description' => 'Frais annuels de la classe de Grade 1A', 'active' => true],
        );
        $tuition = Fee::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id, 'fee_structure_id' => $feeStructure->id, 'name' => 'Scolarité annuelle'],
            ['description' => 'Scolarité', 'amount' => 150000, 'currency' => 'XOF', 'mandatory' => true, 'type' => 'tuition', 'due_date' => '2026-10-01', 'sort_order' => 1, 'active' => true],
        );
        $invoice = Invoice::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id, 'number' => 'FAC-DEMO-0001'],
            ['student_id' => $student->id, 'academic_year_id' => $year->id, 'fee_structure_id' => $feeStructure->id, 'class_room_id' => $class->id, 'total_amount' => 150000, 'currency' => 'XOF', 'due_date' => '2026-10-01', 'status' => Invoice::PARTIALLY_PAID],
        );
        $item = InvoiceItem::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id, 'invoice_id' => $invoice->id, 'fee_id' => $tuition->id],
            ['description' => 'Scolarité annuelle', 'amount' => 150000],
        );
        $payment = Payment::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id, 'reference' => 'PAY-DEMO-0001'],
            ['student_id' => $student->id, 'amount' => 75000, 'currency' => 'XOF', 'payment_method' => 'CASH', 'status' => Payment::CONFIRMED, 'idempotency_key' => 'demo-payment-0001', 'paid_at' => now(), 'received_by' => $accountant->id, 'unallocated_amount' => 0],
        );
        PaymentAllocation::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id, 'payment_id' => $payment->id, 'invoice_id' => $invoice->id],
            ['amount' => 75000],
        );
        Receipt::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id, 'payment_id' => $payment->id],
            ['number' => 'REC-DEMO-0001', 'balance_after' => 75000],
        );
        $invoice->refreshStatus();
    }
}
