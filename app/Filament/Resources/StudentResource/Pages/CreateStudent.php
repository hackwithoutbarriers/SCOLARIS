<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Guardian;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Resources\Pages\CreateRecord;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $student = Student::create($data);

            $guardian = Guardian::create([
                'first_name' => $data['guardian_first_name'],
                'last_name' => $data['guardian_last_name'],
                'name' => trim($data['guardian_first_name'].' '.$data['guardian_last_name']),
                'relationship' => $data['guardian_relationship'],
                'phone' => $data['guardian_phone'],
                'email' => $data['guardian_email'] ?? null,
                'address' => $data['guardian_address'] ?? null,
                'active' => true,
            ]);
            $student->guardians()->attach($guardian, ['is_primary' => true, 'receives_sms' => true, 'receives_whatsapp' => true]);

            if (! empty($data['second_guardian_first_name']) && ! empty($data['second_guardian_last_name']) && ! empty($data['second_guardian_phone'])) {
                $secondGuardian = Guardian::create([
                    'first_name' => $data['second_guardian_first_name'],
                    'last_name' => $data['second_guardian_last_name'],
                    'name' => trim($data['second_guardian_first_name'].' '.$data['second_guardian_last_name']),
                    'relationship' => $data['second_guardian_relationship'] ?? null,
                    'phone' => $data['second_guardian_phone'],
                    'email' => $data['second_guardian_email'] ?? null,
                    'active' => true,
                ]);
                $student->guardians()->attach($secondGuardian, ['is_primary' => false, 'receives_sms' => true, 'receives_whatsapp' => true]);
            }

            Enrollment::create([
                'student_id' => $student->id,
                'academic_year_id' => $data['enrollment_academic_year_id'],
                'class_room_id' => $data['enrollment_class_room_id'],
                'enrollment_date' => $data['enrollment_date'],
                'enrolled_at' => $data['enrollment_date'],
                'status' => 'active',
            ]);

            return $student;
        });
    }
}
