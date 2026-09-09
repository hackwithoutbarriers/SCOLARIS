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
        return app(GradeCalculationService::class)->saveGrade(
            Assessment::findOrFail($data['assessment_id']),
            Student::findOrFail($data['student_id']),
            $data['score'],
            ['remarks' => $data['remarks'] ?? null],
        );
    }
}
