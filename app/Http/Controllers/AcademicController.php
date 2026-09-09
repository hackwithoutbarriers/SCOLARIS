<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\ReportCard;
use App\Models\Student;
use App\Services\GradeCalculationService;
use App\Services\GradeCsvImporter;
use App\Services\ReportCardRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicController extends Controller
{
    public function grades(Student $student, GradeCalculationService $calculator, ?int $term = null): JsonResponse
    {
        return response()->json($calculator->calculate($student, $term ? \App\Models\Term::findOrFail($term) : null));
    }

    public function storeGrade(Request $request, Assessment $assessment, GradeCalculationService $calculator): JsonResponse
    {
        $data = $request->validate(['student_id' => ['required', 'integer'], 'score' => ['required', 'numeric'], 'remarks' => ['nullable', 'string']]);
        $student = Student::findOrFail($data['student_id']);
        $grade = $calculator->saveGrade($assessment, $student, $data['score'], ['remarks' => $data['remarks'] ?? null]);
        return response()->json($grade->load('assessment', 'student'), 201);
    }

    public function importGrades(Request $request, GradeCsvImporter $importer): JsonResponse
    {
        $data = $request->validate(['file' => ['required', 'file', 'mimes:csv,txt'], 'school_id' => ['nullable', 'integer']]);
        abort_unless(auth()->user()->isSuperAdmin() || (int) ($data['school_id'] ?? auth()->user()->school_id) === auth()->user()->school_id, 403);
        return response()->json($importer->import($data['file']->getRealPath(), (int) ($data['school_id'] ?? auth()->user()->school_id)));
    }

    public function reportCardHtml(ReportCard $reportCard, ReportCardRenderer $renderer): \Illuminate\Http\Response
    {
        return response($renderer->renderHtml($reportCard))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function reportCardPdf(ReportCard $reportCard, ReportCardRenderer $renderer): mixed
    {
        return $renderer->pdf($reportCard)->download('report-card-'.$reportCard->id.'.pdf');
    }
}
