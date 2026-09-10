<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Services\StudentCardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class StudentCardController extends Controller
{
    public function individual(Request $request, Student $student, StudentCardService $cards)
    {
        $this->authorizeDirector($request, $student->school_id);
        $student->load(['school', 'enrollments.classRoom']);
        return Pdf::loadView('students.card', ['students' => collect([$student]), 'cards' => $cards])
            ->setPaper('a4')->download('carte-'.$student->student_number.'.pdf');
    }

    public function classBatch(Request $request, ClassRoom $classRoom, StudentCardService $cards)
    {
        $this->authorizeDirector($request, $classRoom->school_id);
        $students = $cards->cardsForClass($classRoom);
        return Pdf::loadView('students.card', compact('students', 'cards'))
            ->setPaper('a4')->download('cartes-'.str($classRoom->name)->slug().'.pdf');
    }

    public function verify(string $token)
    {
        abort_unless(Student::query()->where('card_token', $token)->exists(), 404);
        return response()->json(['valid' => true]);
    }

    private function authorizeDirector(Request $request, int $schoolId): void
    {
        abort_unless($request->user()?->isDirector() && (int) $request->user()->school_id === $schoolId, 403);
    }
}
