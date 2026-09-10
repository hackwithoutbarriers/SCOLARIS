<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\ReportCard;
use App\Models\ReportCardTemplate;
use App\Models\ReportCardVersion;
use App\Models\ConductLabel;
use App\Models\Student;
use App\Models\Term;
use App\Support\Academic\ReportCardData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ReportCardService
{
    public function __construct(private readonly GradeCalculationService $calculator) {}

    public function generate(Student $student, AcademicYear $year, ?Term $term = null, ?ReportCardTemplate $template = null, ?array $conduct = null): ReportCard
    {
        if ($student->school_id !== $year->school_id || ($term && $term->school_id !== $student->school_id) || ($template && $template->school_id !== $student->school_id)) {
            throw ValidationException::withMessages(['school' => 'Les éléments du bulletin doivent appartenir à la même école.']);
        }
        $payload = $this->calculator->calculate($student, $term);
        if ($term === null) {
            $payload['academic_mention'] = $this->calculator->mentionFor($student, $year, (float) ($payload['summary']['average'] ?? 0));
            $payload['summary']['academic_mention'] = $payload['academic_mention'];
        }
        $attendance = $this->attendanceSummary($student, $year, $term);
        $school = $student->school;
        $enrollment = $student->enrollments()->where('academic_year_id', $year->id)->latest('id')->first();
        $ranking = $this->calculator->rankings($student, $term);
        $data = ReportCardData::fromArray(array_merge($payload, [
            'school_identity' => $school ? ['id' => $school->id, 'name' => $school->name, 'address' => $school->address, 'phone' => $school->phone, 'logo_path' => $school->logo_path] : [],
            'class' => $enrollment?->classRoom ? ['id' => $enrollment->classRoom->id, 'name' => $enrollment->classRoom->name, 'grade_level' => $enrollment->classRoom->grade_level, 'cycle' => $enrollment->classRoom->cycle, 'filiere' => $enrollment->classRoom->filiere] : [],
            'period' => array_merge($payload['period'], ['academic_year_id' => $year->id, 'academic_year' => $year->name]),
            'appreciation' => array_merge($payload['appreciation'], ['rank' => $ranking['rank']]),
            'ranking' => $ranking,
            'academic_mention' => $payload['academic_mention'] ?? [],
            'conduct' => $conduct ?? [],
            'attendance' => $this->withAttendanceTotals($attendance),
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
        Gate::authorize('viewReportCard', $card);

        return $this->transition($card, 'draft', 'review');
    }

    public function approve(ReportCard $card): ReportCard
    {
        Gate::authorize('update', $card);
        abort_unless(auth()->user()->isDirector(), 403);

        return $this->transition($card, 'conseil_de_classe', 'approved');
    }

    public function submitToClassCouncil(ReportCard $card): ReportCard
    {
        Gate::authorize('update', $card);
        abort_unless(auth()->user()->isDirector(), 403);

        return $this->transition($card, 'review', 'conseil_de_classe');
    }

    public function publish(ReportCard $card): ReportCard
    {
        Gate::authorize('update', $card);
        abort_unless(auth()->user()->isDirector(), 403);

        $ranking = $this->calculator->rankings($card->student, $card->term);
        if ($ranking['provisional'] === true) {
            throw ValidationException::withMessages(['ranking' => 'Le classement reste provisoire tant que le contrôle des notes n’est pas validé.']);
        }
        $data = $card->normalizedData()?->toArray() ?? [];
        if (($data['ranking'] ?? []) !== $ranking) {
            $data['ranking'] = $ranking;
            $data['appreciation'] = array_merge($data['appreciation'] ?? [], ['rank' => $ranking['rank']]);
            $card->update(['data' => $data]);
        }

        return $this->transition($card, 'approved', 'published');
    }

    public function requestRevision(ReportCard $card): ReportCard
    {
        Gate::authorize('update', $card);
        abort_unless(auth()->user()->isDirector(), 403);

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

    private function withAttendanceTotals(array $attendance): array
    {
        $justified = (int) ($attendance['days_excused'] ?? 0);
        $unjustified = (int) ($attendance['days_absent'] ?? 0);

        return array_merge($attendance, [
            'total_absences' => $justified + $unjustified,
            'absences_justifiees' => $justified,
            'absences_non_justifiees' => $unjustified,
            'total_retards' => (int) ($attendance['days_late'] ?? 0),
        ]);
    }

    public function overrideAcademicMention(ReportCard $card, string $mention, string $reason): ReportCard
    {
        Gate::authorize('update', $card);
        abort_unless(auth()->user()->isDirector(), 403);
        if ($card->status === 'published' || $card->term?->isClosed()) {
            throw ValidationException::withMessages(['mention' => 'La mention ne peut plus être modifiée après publication ou clôture.']);
        }
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Le motif de la dérogation est obligatoire.']);
        }

        $data = $card->normalizedData()?->toArray() ?? [];
        $data['academic_mention'] = [
            'label' => $mention,
            'overridden' => true,
            'override_reason' => $reason,
            'override_by' => auth()->id(),
            'override_at' => now()->toIso8601String(),
        ];
        $card->update([
            'data' => $data,
            'mention_override' => $mention,
            'mention_override_reason' => $reason,
            'mention_override_by' => auth()->id(),
            'mention_override_at' => now(),
        ]);
        $this->snapshot($card);

        return $card->refresh();
    }

    public function setConduct(ReportCard $card, string $label, ?string $comment = null): ReportCard
    {
        Gate::authorize('update', $card);
        abort_unless(auth()->user()->isDirector(), 403);
        if ($card->status === 'published' || $card->term?->isClosed()) {
            throw ValidationException::withMessages(['conduct' => 'La conduite ne peut plus être modifiée après publication ou clôture.']);
        }
        if (! ConductLabel::query()->where('school_id', $card->school_id)->where('label', $label)->where('active', true)->exists()) {
            throw ValidationException::withMessages(['conduct' => 'Ce libellé de conduite n’est pas configuré pour l’école.']);
        }
        $data = $card->normalizedData()?->toArray() ?? [];
        $data['conduct'] = array_filter(['label' => $label, 'comment' => $comment], static fn ($value): bool => $value !== null && $value !== '');
        $card->update(['data' => $data]);
        $this->snapshot($card);

        return $card->refresh();
    }

    /**
     * A correction invalidates every non-published ranking in the period, not
     * only the bulletin of the learner whose grade was edited.
     */
    public function refreshDraftRankings(Term $term): void
    {
        ReportCard::query()->where('term_id', $term->id)
            ->whereIn('status', ['draft', 'review', 'approved', 'revision_requested'])
            ->get()->each(function (ReportCard $card): void {
                $ranking = $this->calculator->rankings($card->student, $card->term);
                $data = $card->normalizedData()?->toArray() ?? [];
                $data['ranking'] = $ranking;
                $data['appreciation'] = array_merge($data['appreciation'] ?? [], ['rank' => $ranking['rank']]);
                $card->update(['data' => $data]);
                $this->snapshot($card);
            });
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
        if ($card->term?->isClosed()) {
            throw ValidationException::withMessages(['term' => 'Cette période est clôturée et ne peut plus être modifiée.']);
        }

        if ($card->status !== $from) {
            throw ValidationException::withMessages(['status' => "Le bulletin doit être à l’état « {$from} » avant de passer à « {$to} »."]);
        }
        $card->update(['status' => $to]);
        $this->snapshot($card);

        return $card->refresh();
    }
}
