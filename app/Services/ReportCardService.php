<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ReportCard;
use App\Models\ReportCardTemplate;
use App\Models\ReportCardVersion;
use App\Models\Student;
use App\Models\Term;
use App\Support\Academic\ReportCardData;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReportCardService
{
    public function __construct(private readonly GradeCalculationService $calculator) {}

    public function generate(Student $student, AcademicYear $year, ?Term $term = null, ?ReportCardTemplate $template = null): ReportCard
    {
        if ($student->school_id !== $year->school_id || ($term && $term->school_id !== $student->school_id) || ($template && $template->school_id !== $student->school_id)) {
            throw ValidationException::withMessages(['school' => 'Les éléments du bulletin doivent appartenir à la même école.']);
        }
        $payload = $this->calculator->calculate($student, $term);
        $attendance = $this->attendanceSummary($student, $year, $term);
        $school = $student->school;
        $enrollment = $student->enrollments()->where('academic_year_id', $year->id)->latest('id')->first();
        $data = ReportCardData::fromArray(array_merge($payload, [
            'school_identity' => $school ? ['id' => $school->id, 'name' => $school->name, 'address' => $school->address, 'phone' => $school->phone, 'logo_path' => $school->logo_path] : [],
            'class' => $enrollment?->classRoom ? ['id' => $enrollment->classRoom->id, 'name' => $enrollment->classRoom->name, 'grade_level' => $enrollment->classRoom->grade_level] : [],
            'period' => array_merge($payload['period'], ['academic_year_id' => $year->id, 'academic_year' => $year->name]),
            'appreciation' => array_merge($payload['appreciation'], ['rank' => $this->calculator->rank($student, $term)]),
            'ranking' => ['rank' => $this->calculator->rank($student, $term)],
            'attendance_summary' => $attendance,
        ]));

        $latest = ReportCard::query()->where('student_id', $student->id)->where('academic_year_id', $year->id)
            ->when($term, fn ($q) => $q->where('term_id', $term->id), fn ($q) => $q->whereNull('term_id'))
            ->latest('version')->first();
        $version = ($latest?->version ?? 0) + 1;

        return DB::transaction(function () use ($student, $year, $term, $template, $version, $data): ReportCard {
            $card = ReportCard::create([
                'school_id' => $student->school_id, 'student_id' => $student->id, 'academic_year_id' => $year->id,
                'term_id' => $term?->id, 'template_id' => $template?->id, 'version' => $version,
                'status' => 'draft', 'data' => $data->toArray(), 'generated_by' => auth()->id(), 'generated_at' => now(),
            ]);
            $this->snapshot($card);

            return $card;
        });
    }

    public function submitForReview(ReportCard $card): ReportCard
    {
        return $this->transition($card, 'draft', 'review');
    }

    public function approve(ReportCard $card): ReportCard
    {
        return $this->transition($card, 'review', 'approved');
    }

    public function publish(ReportCard $card): ReportCard
    {
        return $this->transition($card, 'approved', 'published');
    }

    public function requestRevision(ReportCard $card): ReportCard
    {
        return $this->transition($card, 'published', 'revision_requested');
    }

    /**
     * Attendance is presentation data only and is scoped to the report period.
     *
     * @return array{days_present:int,days_absent:int,days_late:int,days_excused:int,total_days:int}
     */
    public function attendanceSummary(Student $student, AcademicYear $year, ?Term $term = null): array
    {
        $records = AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->whereHas('session', function ($query) use ($year, $term): void {
                $query->where('academic_year_id', $year->id)
                    ->where('status', 'VALIDATED')
                    ->when($term, fn ($q) => $q->whereBetween('session_date', [$term->starts_at, $term->ends_at]));
            })->get();

        return [
            'days_present' => $records->where('status', 'PRESENT')->count(),
            'days_absent' => $records->where('status', 'ABSENT')->count(),
            'days_late' => $records->where('status', 'LATE')->count(),
            'days_excused' => $records->where('status', 'EXCUSED')->count(),
            'total_days' => $records->count(),
        ];
    }

    public function snapshot(ReportCard $card): ReportCardVersion
    {
        $version = (int) ($card->versions()->max('version') ?? 0) + 1;

        return $card->versions()->create([
            'school_id' => $card->school_id, 'version' => $version,
            'status' => $card->status, 'data' => $card->data ?? [], 'created_by' => auth()->id(),
        ]);
    }

    private function transition(ReportCard $card, string $from, string $to): ReportCard
    {
        if ($card->status !== $from) {
            throw ValidationException::withMessages(['status' => "Le bulletin doit être à l’état « {$from} » avant de passer à « {$to} »."]);
        }
        $card->update(['status' => $to]);
        $this->snapshot($card);

        return $card->refresh();
    }
}
