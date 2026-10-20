<?php

namespace App\Filament\Resources\GradeResource\Pages;

use App\Filament\Resources\GradeResource;
use App\Models\Assessment;
use App\Models\Student;
use App\Services\GradeCalculationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateGrade extends CreateRecord
{
    protected static string $resource = GradeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $assessment = Assessment::findOrFail($data['assessment_id']);
        $student = Student::findOrFail($data['student_id']);
        abort_unless(auth()->user()->can('manageGrades', [$assessment, $student]), 403);

        return app(GradeCalculationService::class)->saveGrade(
            $assessment,
            $student,
            $data['score'],
            ['remarks' => $data['remarks'] ?? null],
        );
    }
}
