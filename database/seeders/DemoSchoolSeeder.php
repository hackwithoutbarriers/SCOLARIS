<?php

namespace Database\Seeders;

use App\Models\AbsenceFollowUp;
use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Certificate;
use App\Models\ClassRoom;
use App\Models\Expense;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\TimetableSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSchoolSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('DemoSchoolSeeder est réservé aux environnements de développement.');
        }

        fake()->seed(20260911);

        $school = School::withoutGlobalScopes()->updateOrCreate(
            ['code' => 'DEMO-TOGO'],
            [
                'name' => 'École Lumière de Lomé',
                'slug' => 'ecole-lumiere-lome',
                'city' => 'Lomé',
                'country' => 'Togo',
                'timezone' => 'Africa/Lome',
                'active' => true,
                'expense_approval_threshold' => 100000,
            ],
        );

        $year = AcademicYear::withoutGlobalScopes()->updateOrCreate(
            ['school_id' => $school->id, 'name' => '2026-2027'],
            ['start_date' => '2026-09-01', 'end_date' => '2027-07-31', 'status' => 'active', 'is_current' => true],
        );

        $users = $this->seedUsers($school);
        $classes = $this->seedClasses($school, $year);
        $subjects = $this->seedSubjects($school);
        $this->seedAssignments($school, $year, $classes, $subjects, $users['teachers']);
        $this->command?->info('Structure scolaire créée.');
        $students = \Illuminate\Database\Eloquent\Model::withoutEvents(fn (): array => $this->seedStudents($school, $year, $classes));
        $this->command?->info('500 élèves et leurs tuteurs créés.');
        $this->seedAttendance($school, $year, $classes, $students, $users['teachers'], $users['secretary']);
        $this->command?->info('Présences et suivis créés.');
        $this->seedCertificates($school, $students, $users['secretary'], $users['director']);
        $this->seedTimetable($school, $year, $classes, $subjects, $users['teachers']);
        $this->seedFinance($school, $students, $users['accountant']);

        $this->command?->info('Données de démonstration créées pour l’École Lumière de Lomé.');
        $this->command?->info('Comptes: direction@demo.scolaris.test, secretariat@demo.scolaris.test, caisse@demo.scolaris.test');
        $this->command?->info('Mot de passe de développement: DemoScolaris!2026');
    }

    private function seedUsers(School $school): array
    {
        $password = Hash::make('DemoScolaris!2026');
        $definitions = [
            'director' => ['direction@demo.scolaris.test', 'Kossi', 'Mensah', 'director'],
            'secretary' => ['secretariat@demo.scolaris.test', 'Afi', 'Koudjo', 'secretary'],
            'accountant' => ['caisse@demo.scolaris.test', 'Yao', 'Améto', 'accountant'],
        ];

        $users = [];
        foreach ($definitions as $key => [$email, $firstName, $lastName, $role]) {
            $users[$key] = User::withoutGlobalScopes()->updateOrCreate(
                ['email' => $email],
                ['school_id' => $school->id, 'name' => $firstName.' '.$lastName, 'first_name' => $firstName, 'last_name' => $lastName, 'role' => $role, 'password' => $password, 'is_active' => true, 'email_verified_at' => now()],
            );
        }

        $users['hybrid'] = User::withoutGlobalScopes()->updateOrCreate(
            ['email' => 'assistante-enseignante@demo.scolaris.test'],
            ['school_id' => $school->id, 'name' => 'Esi Adjei', 'first_name' => 'Esi', 'last_name' => 'Adjei', 'role' => 'secretary', 'secondary_role' => 'teacher', 'password' => $password, 'is_active' => true, 'email_verified_at' => now()],
        );

        $users['teachers'] = [];
        for ($index = 1; $index <= 12; $index++) {
            $users['teachers'][] = User::withoutGlobalScopes()->updateOrCreate(
                ['email' => sprintf('enseignant%02d@demo.scolaris.test', $index)],
                ['school_id' => $school->id, 'name' => 'Enseignant '.$index, 'first_name' => 'Enseignant', 'last_name' => (string) $index, 'role' => 'teacher', 'password' => $password, 'is_active' => true, 'email_verified_at' => now()],
            );
        }

        return $users;
    }

    private function seedClasses(School $school, AcademicYear $year): array
    {
        $definitions = [
            ['primaire', 'CI'], ['primaire', 'CP'], ['primaire', 'CE1'], ['primaire', 'CE2'], ['primaire', 'CM1'], ['primaire', 'CM2'],
            ['college', '6e'], ['college', '5e'], ['college', '4e'], ['college', '3e'],
            ['lycee', 'Seconde'], ['lycee', 'Première'], ['lycee', 'Terminale'],
        ];

        return collect($definitions)->flatMap(function (array $definition) use ($school, $year): array {
            return collect(['A', 'B'])->map(function (string $section) use ($school, $year, $definition): ClassRoom {
                [$cycle, $level] = $definition;

                return ClassRoom::withoutGlobalScopes()->updateOrCreate(
                    ['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => $level.' '.$section],
                    ['cycle' => $cycle, 'grade_level' => strtolower($level), 'capacity' => 40],
                );
            })->all();
        })->values()->all();
    }

    private function seedSubjects(School $school): array
    {
        return collect(['Français', 'Mathématiques', 'Anglais', 'Sciences', 'Histoire-Géographie', 'Éducation physique'])
            ->map(fn (string $name): Subject => Subject::withoutGlobalScopes()->updateOrCreate(
                ['school_id' => $school->id, 'name' => $name],
                ['code' => Str::upper(Str::substr(Str::slug($name, ''), 0, 5))],
            ))->all();
    }

    private function seedAssignments(School $school, AcademicYear $year, array $classes, array $subjects, array $teachers): void
    {
        foreach ($classes as $classIndex => $class) {
            foreach ($subjects as $subjectIndex => $subject) {
                TeacherAssignment::withoutGlobalScopes()->firstOrCreate([
                    'school_id' => $school->id, 'teacher_id' => $teachers[($classIndex + $subjectIndex) % count($teachers)]->id,
                    'class_room_id' => $class->id, 'subject_id' => $subject->id, 'academic_year_id' => $year->id,
                ]);
            }
        }
    }

    private function seedStudents(School $school, AcademicYear $year, array $classes): array
    {
        $now = now();
        $studentNumbers = [];
        $studentRows = [];
        $guardianRows = [];

        for ($index = 1; $index <= 500; $index++) {
            $number = sprintf('DEMO-%04d', $index);
            $studentNumbers[] = $number;
            $studentRows[] = [
                'school_id' => $school->id, 'student_number' => $number, 'admission_number' => $number,
                'first_name' => fake()->firstName(), 'last_name' => fake()->lastName(),
                'date_of_birth' => fake()->dateTimeBetween('-18 years', '-5 years')->format('Y-m-d'),
                'place_of_birth' => 'Lomé', 'gender' => fake()->randomElement(['male', 'female']),
                'active' => true, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
            ];
            $guardianRows[] = [
                'school_id' => $school->id,
                'phone' => '+228'.str_pad((string) (90000000 + $index), 8, '0', STR_PAD_LEFT),
                'first_name' => fake()->firstName(), 'last_name' => fake()->lastName(),
                'name' => fake()->name(), 'relationship' => fake()->randomElement(['Parent', 'Tuteur', 'Mère', 'Père']),
                'email' => 'tuteur'.$index.'@demo.scolaris.test', 'active' => true,
                'created_at' => $now, 'updated_at' => $now,
            ];
        }

        $existingNumbers = Student::withoutGlobalScopes()->where('school_id', $school->id)->whereIn('student_number', $studentNumbers)->pluck('student_number')->all();
        $missingStudents = array_values(array_filter($studentRows, fn (array $row): bool => ! in_array($row['student_number'], $existingNumbers, true)));
        foreach (array_chunk($missingStudents, 100) as $chunk) {
            DB::table('students')->insert($chunk);
        }

        $guardianPhones = array_column($guardianRows, 'phone');
        $existingPhones = Guardian::withoutGlobalScopes()->where('school_id', $school->id)->whereIn('phone', $guardianPhones)->pluck('phone')->all();
        $missingGuardians = array_values(array_filter($guardianRows, fn (array $row): bool => ! in_array($row['phone'], $existingPhones, true)));
        foreach (array_chunk($missingGuardians, 100) as $chunk) {
            DB::table('guardians')->insert($chunk);
        }

        $students = Student::withoutGlobalScopes()->where('school_id', $school->id)->whereIn('student_number', $studentNumbers)->orderBy('student_number')->get()->values()->all();
        $guardians = Guardian::withoutGlobalScopes()->where('school_id', $school->id)->whereIn('phone', $guardianPhones)->orderBy('phone')->get()->values()->all();
        $pivotRows = [];
        $enrollmentKeys = [];
        foreach ($students as $index => $student) {
            $class = $classes[$index % count($classes)];
            $guardian = $guardians[$index];
            $pivotRows[] = [
                'guardian_id' => $guardian->id, 'student_id' => $student->id, 'is_primary' => true,
                'receives_sms' => false, 'receives_whatsapp' => true, 'created_at' => $now, 'updated_at' => $now,
            ];
            $enrollmentKeys[] = ['student_id' => $student->id, 'class_room_id' => $class->id];
        }
        foreach (array_chunk($pivotRows, 100) as $chunk) {
            DB::table('guardian_student')->insertOrIgnore($chunk);
        }

        $enrolledStudentIds = DB::table('enrollments')
            ->where('school_id', $school->id)->where('academic_year_id', $year->id)
            ->whereIn('student_id', array_column($students, 'id'))->pluck('student_id')->all();
        $enrollments = [];
        foreach ($students as $index => $student) {
            if (in_array($student->id, $enrolledStudentIds, true)) {
                continue;
            }
            $enrollments[] = [
                'school_id' => $school->id, 'student_id' => $student->id,
                'class_room_id' => $enrollmentKeys[$index]['class_room_id'], 'academic_year_id' => $year->id,
                'enrollment_date' => '2026-09-01', 'enrolled_at' => '2026-09-01', 'status' => 'active',
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        foreach (array_chunk($enrollments, 100) as $chunk) {
            DB::table('enrollments')->insert($chunk);
        }

        return $students;
    }

    private function seedAttendance(School $school, AcademicYear $year, array $classes, array $students, array $teachers, User $secretary): void
    {
        $now = now();
        $sessionRows = [];
        foreach (range(0, 9) as $day) {
            foreach ($classes as $classIndex => $class) {
                $sessionRows[] = [
                    'school_id' => $school->id, 'academic_year_id' => $year->id, 'class_room_id' => $class->id,
                    'teacher_id' => $teachers[$classIndex % count($teachers)]->id,
                    'session_date' => now()->subDays($day)->toDateString(), 'started_at' => now()->subDays($day)->setTime(7, 30),
                    'validated_at' => now()->subDays($day)->setTime(8, 0), 'status' => 'VALIDATED',
                    'sync_id' => (string) Str::uuid(), 'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
        $existingSessions = AttendanceSession::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->whereIn('class_room_id', array_column($sessionRows, 'class_room_id'))
            ->whereIn('session_date', array_column($sessionRows, 'session_date'))
            ->get(['id', 'class_room_id', 'session_date'])
            ->keyBy(fn (AttendanceSession $session): string => $session->class_room_id.'-'.\Carbon\Carbon::parse($session->session_date)->format('Y-m-d'));
        $missingSessions = array_values(array_filter($sessionRows, fn (array $row): bool => ! $existingSessions->has($row['class_room_id'].'-'.$row['session_date'])));
        foreach (array_chunk($missingSessions, 100) as $chunk) {
            DB::table('attendance_sessions')->insertOrIgnore($chunk);
        }
        $sessions = AttendanceSession::withoutGlobalScopes()
            ->where('school_id', $school->id)
            ->whereIn('class_room_id', array_column($sessionRows, 'class_room_id'))
            ->whereIn('session_date', array_column($sessionRows, 'session_date'))
            ->get()
            ->keyBy(fn (AttendanceSession $session): string => $session->class_room_id.'-'.\Carbon\Carbon::parse($session->session_date)->format('Y-m-d'));
        $recordRows = [];
        $followUpRows = [];
        foreach (range(0, 9) as $day) {
            foreach ($classes as $classIndex => $class) {
                $date = now()->subDays($day)->toDateString();
                $session = $sessions->get($class->id.'-'.$date);
                foreach (array_slice($students, $classIndex * 36, 36) as $studentIndex => $student) {
                    $status = $studentIndex % 17 === 0 ? 'ABSENT' : ($studentIndex % 19 === 0 ? 'LATE' : 'PRESENT');
                    $clientOperationId = sprintf(
                        '%s-%s-4%s-%s-%s',
                        substr(md5('demo-'.$session->id.'-'.$student->id), 0, 8),
                        substr(md5('demo-'.$session->id.'-'.$student->id), 8, 4),
                        substr(md5('demo-'.$session->id.'-'.$student->id), 13, 3),
                        substr(md5('demo-'.$session->id.'-'.$student->id), 16, 4),
                        substr(md5('demo-'.$session->id.'-'.$student->id), 20, 12),
                    );
                    $recordRows[] = [
                        'school_id' => $school->id, 'attendance_session_id' => $session->id, 'student_id' => $student->id,
                        'status' => $status, 'late_minutes' => $status === 'LATE' ? 15 : null,
                        'marked_at' => now()->subDays($day), 'marked_by' => $teachers[$classIndex % count($teachers)]->id,
                        'client_operation_id' => $clientOperationId, 'created_at' => $now, 'updated_at' => $now,
                    ];
                    if (in_array($status, ['ABSENT', 'LATE'], true)) {
                        $followUpRows[] = [
                            'school_id' => $school->id, 'attendance_session_id' => $session->id, 'student_id' => $student->id,
                            'status' => $studentIndex % 3 === 0 ? 'TO_CONTACT' : 'CONTACTED', 'channel' => 'WhatsApp',
                            'contacted_by' => $studentIndex % 3 === 0 ? null : $secretary->id,
                            'contacted_at' => $studentIndex % 3 === 0 ? null : now(),
                            'notes' => $studentIndex % 3 === 0 ? null : 'Parent joint et retour attendu.',
                        ];
                    }
                }
            }
        }
        foreach (array_chunk($recordRows, 500) as $chunk) {
            DB::table('attendance_records')->insertOrIgnore($chunk);
        }
        $recordIds = AttendanceRecord::withoutGlobalScopes()->where('school_id', $school->id)
            ->whereIn('attendance_session_id', array_column($followUpRows, 'attendance_session_id'))
            ->whereIn('student_id', array_column($followUpRows, 'student_id'))
            ->get(['id', 'attendance_session_id', 'student_id'])
            ->keyBy(fn (AttendanceRecord $record): string => $record->attendance_session_id.'-'.$record->student_id);
        $followUps = array_map(function (array $row) use ($recordIds, $now): array {
            $record = $recordIds[$row['attendance_session_id'].'-'.$row['student_id']] ?? null;
            if (! $record) {
                return [];
            }

            return [
                'school_id' => $row['school_id'], 'attendance_record_id' => $record->id,
                'status' => $row['status'], 'channel' => $row['channel'], 'contacted_by' => $row['contacted_by'],
                'contacted_at' => $row['contacted_at'], 'notes' => $row['notes'], 'created_at' => $now, 'updated_at' => $now,
            ];
        }, $followUpRows);
        $followUps = array_values(array_filter($followUps));
        foreach (array_chunk($followUps, 500) as $chunk) {
            DB::table('absence_follow_ups')->insertOrIgnore($chunk);
        }
    }

    private function seedCertificates(School $school, array $students, User $secretary, User $director): void
    {
        foreach ([
            ['draft', $secretary], ['pending_validation', $secretary], ['validated', $director], ['delivered', $director], ['cancelled', $director],
        ] as $index => [$status, $requester]) {
            Certificate::withoutGlobalScopes()->updateOrCreate(
                ['school_id' => $school->id, 'student_id' => $students[$index]->id, 'document_type' => 'certificate'],
                ['data' => ['demo' => true, 'workflow_note' => 'Donnée de démonstration'], 'status' => $status, 'requested_by' => $requester->id, 'number' => in_array($status, ['validated', 'delivered'], true) ? 'CERT-DEMO-'.($index + 1) : null, 'validated_by' => in_array($status, ['validated', 'delivered'], true) ? $director->id : null, 'validated_at' => in_array($status, ['validated', 'delivered'], true) ? now() : null, 'delivery_method' => $status === 'delivered' ? 'school' : null, 'delivered_to' => $status === 'delivered' ? 'Parent de l’élève' : null, 'delivered_by' => $status === 'delivered' ? $secretary->id : null, 'delivered_at' => $status === 'delivered' ? now() : null, 'cancellation_reason' => $status === 'cancelled' ? 'Demande remplacée par une nouvelle inscription.' : null],
            );
        }
    }

    private function seedTimetable(School $school, AcademicYear $year, array $classes, array $subjects, array $teachers): void
    {
        foreach (array_slice($classes, 0, 6) as $index => $class) {
            TimetableSlot::withoutGlobalScopes()->updateOrCreate(
                ['school_id' => $school->id, 'academic_year_id' => $year->id, 'class_room_id' => $class->id, 'day_of_week' => 1, 'starts_at' => '08:00', 'ends_at' => '09:00'],
                ['teacher_id' => $teachers[$index % count($teachers)]->id, 'subject_id' => $subjects[$index % count($subjects)]->id, 'room' => 'Salle '.($index + 1), 'status' => $index % 2 === 0 ? 'published' : 'draft', 'version' => 1],
            );
        }
    }

    private function seedFinance(School $school, array $students, User $accountant): void
    {
        foreach (range(1, 24) as $index) {
            Expense::withoutGlobalScopes()->updateOrCreate(
                ['school_id' => $school->id, 'voucher_number' => 'DEP-DEMO-'.str_pad((string) $index, 3, '0', STR_PAD_LEFT)],
                ['category' => fake()->randomElement(['Fournitures scolaires', 'Transport', 'Entretien', 'Eau et électricité']), 'amount' => fake()->randomElement([15000, 35000, 75000, 125000]), 'description' => 'Dépense de fonctionnement de démonstration', 'expense_date' => now()->subDays($index), 'recorded_by' => $accountant->id, 'approval_status' => $index % 5 === 0 ? 'pending' : ($index % 7 === 0 ? 'rejected' : 'approved'), 'approved_by' => $index % 5 === 0 || $index % 7 === 0 ? null : $accountant->id, 'approved_at' => $index % 5 === 0 || $index % 7 === 0 ? null : now()],
            );
        }
        foreach (array_slice($students, 0, 35) as $index => $student) {
            Payment::withoutGlobalScopes()->updateOrCreate(
                ['school_id' => $school->id, 'reference' => 'PAY-DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                ['student_id' => $student->id, 'amount' => fake()->randomElement([25000, 50000, 75000, 100000]), 'currency' => 'XOF', 'payment_method' => fake()->randomElement(['cash', 'tmoney', 'flooz', 'bank_transfer']), 'status' => 'CONFIRMED', 'paid_at' => now()->subDays($index), 'received_by' => $accountant->id],
            );
        }
    }
}
