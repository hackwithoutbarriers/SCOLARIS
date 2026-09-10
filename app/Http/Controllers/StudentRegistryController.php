<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class StudentRegistryController extends Controller
{
    public function csv(Request $request)
    {
        abort_unless($request->user()?->isDirector(), 403);
        $students = Student::query()->with(['enrollments.classRoom'])->orderBy('student_number')->get();
        return response()->streamDownload(function () use ($students): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Matricule', 'Nom', 'Prénom', 'Date de naissance', 'Sexe', 'Date d’inscription', 'Classe']);
            foreach ($students as $student) {
                $enrollment = $student->enrollments->sortByDesc('id')->first();
                fputcsv($out, [$student->student_number, $student->last_name, $student->first_name, $student->date_of_birth?->format('Y-m-d'), $student->gender, $enrollment?->enrolled_at?->format('Y-m-d'), $enrollment?->classRoom?->name]);
            }
            fclose($out);
        }, 'registre-matricule.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function pdf(Request $request)
    {
        abort_unless($request->user()?->isDirector(), 403);
        $students = Student::query()->with(['enrollments.classRoom'])->orderBy('student_number')->get();
        return Pdf::loadView('students.registry', compact('students'))->download('registre-matricule.pdf');
    }
}
