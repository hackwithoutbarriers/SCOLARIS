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
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

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
            'password' => Hash::make('password'), 'is_active' => true,
        ]);
        User::updateOrCreate(['email' => 'director@example.com'], [
            'name' => 'Demo Director', 'first_name' => 'Demo', 'last_name' => 'Director',
            'school_id' => $school->id, 'role' => 'director', 'password' => Hash::make('password'), 'is_active' => true,
        ]);
        $teacher = User::updateOrCreate(['email' => 'teacher@example.com'], [
            'name' => 'Demo Teacher', 'first_name' => 'Demo', 'last_name' => 'Teacher', 'school_id' => $school->id, 'role' => 'teacher',
            'password' => Hash::make('password'), 'is_active' => true,
        ]);
        $class = ClassRoom::withoutGlobalScopes()->firstOrCreate(['school_id' => $school->id, 'academic_year_id' => $year->id, 'name' => 'Grade 1A'], ['grade_level' => 'Grade 1', 'capacity' => 30]);
        $subject = Subject::withoutGlobalScopes()->firstOrCreate(['school_id' => $school->id, 'name' => 'Mathematics'], ['code' => 'MATH']);
        TeacherAssignment::withoutGlobalScopes()->firstOrCreate([
            'school_id' => $school->id, 'teacher_id' => $teacher->id, 'class_room_id' => $class->id,
            'subject_id' => $subject->id, 'academic_year_id' => $year->id,
        ]);
        $student = Student::withoutGlobalScopes()->firstOrCreate(['school_id' => $school->id, 'student_number' => 'STU-00001'], ['admission_number' => 'STU-00001', 'first_name' => 'Ada', 'last_name' => 'Lovelace', 'active' => true, 'status' => 'active']);
        $guardian = Guardian::withoutGlobalScopes()->firstOrCreate(['school_id' => $school->id, 'phone' => '+10000000000'], ['name' => 'Grace Lovelace', 'relationship' => 'Parent']);
        $student->guardians()->syncWithoutDetaching([$guardian->id => ['is_primary' => true]]);
        Enrollment::withoutGlobalScopes()->firstOrCreate(['school_id' => $school->id, 'student_id' => $student->id, 'academic_year_id' => $year->id], ['class_room_id' => $class->id, 'enrollment_date' => now()->toDateString(), 'enrolled_at' => now()->toDateString(), 'status' => 'active']);
    }
}
