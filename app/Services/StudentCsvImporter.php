<?php

namespace App\Services;

use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use RuntimeException;

final class StudentCsvImporter
{
    private const REQUIRED_HEADERS = [
        'student_number', 'first_name', 'last_name', 'middle_name', 'date_of_birth',
        'gender', 'class', 'guardian_first_name', 'guardian_last_name', 'guardian_phone',
    ];

    /** @return int|array{analyzed:int,created:int,ignored:int,errors:int,duplicates:int,details:array<int,array<string,mixed>>} */
    public function import(string $path, int $schoolId, ?int $academicYearId = null): int|array
    {
        if (!is_file($path)) {
            throw new RuntimeException('Le fichier CSV est introuvable.');
        }

        $csv = Reader::createFromPath($path)->setHeaderOffset(0);
        $headers = array_map('trim', $csv->getHeader());
        if (in_array('admission_number', $headers, true)) {
            return $this->importLegacy($csv, $schoolId);
        }
        $missing = array_values(array_diff(self::REQUIRED_HEADERS, $headers));
        if ($missing !== []) {
            throw new RuntimeException('Colonnes manquantes: '.implode(', ', $missing));
        }

        $result = ['analyzed' => 0, 'created' => 0, 'ignored' => 0, 'errors' => 0, 'duplicates' => 0, 'details' => []];
        $seen = [];
        DB::transaction(function () use ($csv, $schoolId, $academicYearId, &$result, &$seen): void {
            foreach ($csv->getRecords() as $offset => $row) {
                $line = $offset + 2;
                $result['analyzed']++;
                $number = trim((string) ($row['student_number'] ?? ''));
                if (isset($seen[$number]) || Student::withoutGlobalScopes()->where('school_id', $schoolId)->where('student_number', $number)->exists()) {
                    $result['duplicates']++;
                    $result['ignored']++;
                    $result['details'][] = ['line' => $line, 'message' => 'student_number déjà utilisé'];
                    $seen[$number] = true;
                    continue;
                }

                $validator = Validator::make($row, [
                    'student_number' => ['required', 'string', 'max:50'],
                    'first_name' => ['required', 'string', 'max:100'],
                    'last_name' => ['required', 'string', 'max:100'],
                    'date_of_birth' => ['nullable', 'date'],
                    'gender' => ['nullable', 'in:female,male,other'],
                    'guardian_phone' => ['required', 'string', 'max:30'],
                ]);
                if ($validator->fails()) {
                    $result['errors']++;
                    $result['details'][] = ['line' => $line, 'message' => $validator->errors()->first()];
                    continue;
                }

                $student = Student::create([
                    'school_id' => $schoolId, 'student_number' => $number, 'admission_number' => $number,
                    'first_name' => trim($row['first_name']), 'last_name' => trim($row['last_name']),
                    'middle_name' => trim((string) ($row['middle_name'] ?? '')) ?: null,
                    'date_of_birth' => $row['date_of_birth'] ?: null, 'gender' => $row['gender'] ?: null,
                    'active' => true, 'status' => 'active',
                ]);
                $guardian = Guardian::firstOrCreate(
                    ['school_id' => $schoolId, 'phone' => trim($row['guardian_phone'])],
                    ['first_name' => trim($row['guardian_first_name']), 'last_name' => trim($row['guardian_last_name']),
                     'name' => trim($row['guardian_first_name'].' '.$row['guardian_last_name']), 'relationship' => 'Parent', 'active' => true],
                );
                $student->guardians()->syncWithoutDetaching([$guardian->id => ['is_primary' => true]]);

                if ($academicYearId && ($class = ClassRoom::where('school_id', $schoolId)->where('name', trim($row['class']))->first())) {
                    Enrollment::create([
                        'school_id' => $schoolId, 'student_id' => $student->id, 'academic_year_id' => $academicYearId,
                        'class_room_id' => $class->id, 'enrollment_date' => now()->toDateString(), 'status' => 'active',
                    ]);
                }
                $seen[$number] = true;
                $result['created']++;
            }
            if ($result['errors'] > 0) {
                throw new RuntimeException('Import annulé: le fichier contient des lignes invalides.');
            }
        });

        return $result;
    }

    private function importLegacy(Reader $csv, int $schoolId): int
    {
        $count = 0;
        DB::transaction(function () use ($csv, $schoolId, &$count): void {
            foreach ($csv->getRecords() as $row) {
                $student = Student::updateOrCreate(
                    ['school_id' => $schoolId, 'student_number' => trim($row['admission_number'] ?? '')],
                    ['admission_number' => trim($row['admission_number'] ?? ''), 'first_name' => trim($row['first_name'] ?? ''),
                     'last_name' => trim($row['last_name'] ?? ''), 'date_of_birth' => $row['date_of_birth'] ?? null,
                     'status' => 'active', 'active' => true],
                );
                if (!empty($row['guardian_phone'])) {
                    $guardian = Guardian::firstOrCreate(
                        ['school_id' => $schoolId, 'phone' => trim($row['guardian_phone'])],
                        ['name' => trim($row['guardian_name'] ?? ''), 'relationship' => $row['guardian_relationship'] ?? 'Parent', 'active' => true],
                    );
                    $student->guardians()->syncWithoutDetaching([$guardian->id => ['is_primary' => true]]);
                }
                $count++;
            }
        });
        return $count;
    }
}
