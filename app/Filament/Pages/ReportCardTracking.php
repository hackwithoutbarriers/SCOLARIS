<?php

namespace App\Filament\Pages;

use App\Models\ClassRoom;
use App\Models\ReportCard;
use App\Models\Term;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class ReportCardTracking extends Page
{
    protected static ?string $navigationGroup = 'Scolarité';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Suivi des bulletins';

    protected static ?string $title = 'Suivi des bulletins';

    protected static string $view = 'filament.pages.report-card-tracking';

    public ?int $classRoomId = null;

    public ?int $termId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() === true;
    }

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Select::make('classRoomId')->label('Classe')->options(fn (): array => ClassRoom::query()->orderBy('name')->pluck('name', 'id')->all())->live(),
            Select::make('termId')->label('Période')->options(fn (): array => Term::query()->orderByDesc('starts_at')->pluck('name', 'id')->all())->live(),
        ])->columns(2);
    }

    public function getSummary(): array
    {
        $query = ReportCard::query()
            ->when($this->classRoomId, fn ($q) => $q->whereHas('student.enrollments', fn ($e) => $e->where('class_room_id', $this->classRoomId)))
            ->when($this->termId, fn ($q) => $q->where('term_id', $this->termId));

        return [
            'total' => (clone $query)->count(),
            'draft' => (clone $query)->where('status', 'draft')->count(),
            'review' => (clone $query)->where('status', 'review')->count(),
            'approved' => (clone $query)->where('status', 'approved')->count(),
            'published' => (clone $query)->where('status', 'published')->count(),
        ];
    }

    public function getRows(): Collection
    {
        return ReportCard::query()
            ->with(['student', 'term'])
            ->when($this->classRoomId, fn ($q) => $q->whereHas('student.enrollments', fn ($e) => $e->where('class_room_id', $this->classRoomId)))
            ->when($this->termId, fn ($q) => $q->where('term_id', $this->termId))
            ->latest('updated_at')
            ->get();
    }
}
