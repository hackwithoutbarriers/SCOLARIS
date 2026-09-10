<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportCardResource\Pages;
use App\Models\ReportCard;
use App\Services\ReportCardService;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReportCardResource extends Resource
{
    protected static ?string $model = ReportCard::class;

    protected static ?string $navigationGroup = 'Scolarité';

    protected static ?string $navigationLabel = 'Bulletins';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('student.full_name')->searchable(),
            Tables\Columns\TextColumn::make('academicYear.name'),
            Tables\Columns\TextColumn::make('term.name')->placeholder('Annuel'),
            Tables\Columns\TextColumn::make('version'),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('generated_at')->dateTime(),
        ])->actions([
            Tables\Actions\Action::make('submit')
                ->label('Soumettre pour vérification')
                ->icon('heroicon-o-paper-airplane')
                ->visible(fn (ReportCard $record): bool => $record->status === 'draft' && auth()->user()?->role === 'teacher')
                ->requiresConfirmation()
                ->action(function (ReportCard $record): void {
                    app(ReportCardService::class)->submitForReview($record);
                    Notification::make()->success()->title('Bulletin soumis pour vérification.')->send();
                }),
            Tables\Actions\Action::make('approve')
                ->label('Approuver')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (ReportCard $record): bool => $record->status === 'conseil_de_classe' && auth()->user()?->isDirector())
                ->requiresConfirmation()
                ->action(function (ReportCard $record): void {
                    app(ReportCardService::class)->approve($record);
                    Notification::make()->success()->title('Bulletin approuvé.')->send();
                }),
            Tables\Actions\Action::make('classCouncil')
                ->label('Valider par le conseil de classe')
                ->icon('heroicon-o-user-group')
                ->visible(fn (ReportCard $record): bool => $record->status === 'review' && auth()->user()?->isDirector())
                ->requiresConfirmation()
                ->action(function (ReportCard $record): void {
                    app(ReportCardService::class)->submitToClassCouncil($record);
                    Notification::make()->success()->title('Bulletin transmis au conseil de classe.')->send();
                }),
            Tables\Actions\Action::make('publish')
                ->label('Publier')
                ->icon('heroicon-o-megaphone')
                ->visible(fn (ReportCard $record): bool => $record->status === 'approved' && auth()->user()?->isDirector())
                ->requiresConfirmation()
                ->action(function (ReportCard $record): void {
                    app(ReportCardService::class)->publish($record);
                    Notification::make()->success()->title('Bulletin publié.')->send();
                }),
            Tables\Actions\Action::make('overrideMention')
                ->label('Dérogation mention')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (ReportCard $record): bool => $record->status !== 'published' && auth()->user()?->isDirector())
                ->form([
                    \Filament\Forms\Components\TextInput::make('mention')->label('Mention')->required(),
                    \Filament\Forms\Components\Textarea::make('reason')->label('Motif')->required(),
                ])
                ->action(function (ReportCard $record, array $data): void {
                    app(ReportCardService::class)->overrideAcademicMention($record, $data['mention'], $data['reason']);
                    Notification::make()->success()->title('Dérogation enregistrée et auditée.')->send();
                }),
            Tables\Actions\Action::make('html')->url(fn (ReportCard $record) => route('report-cards.html', $record))->openUrlInNewTab(),
            Tables\Actions\Action::make('pdf')->url(fn (ReportCard $record) => route('report-cards.pdf', $record))->openUrlInNewTab(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user?->role === 'teacher') {
            $query->whereHas('student.enrollments.classRoom.teacherAssignments', fn (Builder $assignment) => $assignment->where('teacher_id', $user->id));
        }

        return $query;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListReportCards::route('/')];
    }
}
