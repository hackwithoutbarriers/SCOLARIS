<?php

namespace App\Filament\Resources\GradeResource\Pages;

use App\Filament\Resources\GradeResource;
use App\Models\Assessment;
use App\Models\Student;
use App\Services\GradeCalculationService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditGrade extends EditRecord
{
    protected static string $resource = GradeResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $assessment = Assessment::findOrFail($data['assessment_id']);
        $student = Student::findOrFail($data['student_id']);
        abort_unless(auth()->user()->can('manageGrades', [$assessment, $student]), 403);
        abort_unless(
            (int) $record->assessment_id === (int) $assessment->id
                && (int) $record->student_id === (int) $student->id,
            403,
        );

        return app(GradeCalculationService::class)->saveGrade(
            $assessment,
            $student,
            $data['score'],
            ['remarks' => $data['remarks'] ?? null],
        );
    }
}
