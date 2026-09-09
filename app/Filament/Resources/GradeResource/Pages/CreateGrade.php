<?php
namespace App\Filament\Resources\GradeResource\Pages;
use App\Filament\Resources\GradeResource;
use App\Models\Assessment;
use App\Models\Student;
use App\Services\GradeCalculationService;
use Filament\Resources\Pages\CreateRecord;
class CreateGrade extends CreateRecord {
    protected static string $resource = GradeResource::class;
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model {
        return app(GradeCalculationService::class)->saveGrade(
            Assessment::findOrFail($data['assessment_id']),
            Student::findOrFail($data['student_id']),
            $data['score'],
            ['remarks' => $data['remarks'] ?? null],
        );
    }
}
