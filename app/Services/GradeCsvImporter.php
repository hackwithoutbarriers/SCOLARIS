<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;
use RuntimeException;

final class GradeCsvImporter
{
    private const ID_HEADERS = ['assessment_id', 'student_id', 'score'];
    private const NAME_HEADERS = ['student_number', 'subject', 'assessment', 'score', 'max_score'];

    public function __construct(private readonly GradeCalculationService $calculator) {}

    public function import(string $path, int $schoolId): array
    {
        if (!is_file($path)) throw new RuntimeException('The grade CSV file was not found.');
        $csv = Reader::createFromPath($path)->setHeaderOffset(0);
        $headers = $csv->getHeader();
        $missing = array_diff(self::NAME_HEADERS, $headers);
        $legacy = !array_diff(self::ID_HEADERS, $headers);
        if ($missing && !$legacy) throw new RuntimeException('Missing columns: '.implode(', ', $missing));
        $result = ['analyzed' => 0, 'created' => 0, 'updated' => 0, 'ignored' => 0, 'duplicates' => 0, 'errors' => 0, 'details' => []];
        DB::transaction(function () use ($csv, $schoolId, $legacy, &$result): void {
            foreach ($csv->getRecords() as $offset => $row) {
                $line = $offset + 2;
                $result['analyzed']++;
                $validator = Validator::make($row, $legacy
                    ? ['assessment_id' => ['required', 'integer'], 'student_id' => ['required', 'integer'], 'score' => ['required', 'numeric']]
                    : ['student_number' => ['required', 'string'], 'subject' => ['required', 'string'], 'assessment' => ['required', 'string'], 'score' => ['required', 'numeric'], 'max_score' => ['required', 'numeric']]);
                if ($validator->fails()) { $result['errors']++; $result['details'][] = ['line' => $line, 'message' => $validator->errors()->first()]; continue; }
                if ($legacy) {
                    $assessment = Assessment::withoutGlobalScopes()->where('school_id', $schoolId)->find($row['assessment_id']);
                    $student = Student::withoutGlobalScopes()->where('school_id', $schoolId)->find($row['student_id']);
                } else {
                    $student = Student::withoutGlobalScopes()->where('school_id', $schoolId)->where('student_number', $row['student_number'])->first();
                    $assessment = Assessment::withoutGlobalScopes()->where('school_id', $schoolId)->where('title', $row['assessment'])
                        ->whereHas('subjectConfig.subject', fn ($query) => $query->where('name', $row['subject']))->first();
                }
                if (!$assessment || !$student) { $result['errors']++; $result['details'][] = ['line' => $line, 'message' => 'Assessment or student not found for this school.']; continue; }
                if (!$legacy && (float) $row['max_score'] !== (float) $assessment->max_score) {
                    $result['errors']++; $result['details'][] = ['line' => $line, 'message' => 'CSV max_score does not match the assessment.']; continue;
                }
                $classRoomId = $assessment->subjectConfig?->class_room_id;
                if ($classRoomId && !$student->enrollments()->where('class_room_id', $classRoomId)->exists()) {
                    $result['errors']++; $result['details'][] = ['line' => $line, 'message' => 'Student is not enrolled in the assessment class.']; continue;
                }
                try {
                    $existing = \App\Models\Grade::withoutGlobalScopes()->where('assessment_id', $assessment->id)->where('student_id', $student->id)->exists();
                    $this->calculator->saveGrade($assessment, $student, $row['score']);
                    $result[$existing ? 'updated' : 'created']++;
                    if ($existing) {
                        $result['duplicates']++;
                    }
                } catch (\Illuminate\Validation\ValidationException $exception) {
                    $result['errors']++; $result['details'][] = ['line' => $line, 'message' => $exception->getMessage()];
                }
            }
        });
        return $result;
    }
}
