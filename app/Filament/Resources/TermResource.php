<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TermResource\Pages;
use App\Models\Term;
use App\Services\TermClosureService;
use App\Services\GradeCalculationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TermResource extends Resource
{
    protected static ?string $model = Term::class;

    protected static ?string $navigationGroup = 'Paramétrage scolaire';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('academic_year_id')->relationship('academicYear', 'name')->required(),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\DatePicker::make('starts_at')->required(),
            Forms\Components\DatePicker::make('ends_at')->required(),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(1)->required(),
            Forms\Components\Placeholder::make('status')->label('État')->content(fn (?Term $record): string => $record?->status === 'closed' ? 'Clôturée' : 'Ouverte'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Période'),
            Tables\Columns\TextColumn::make('academicYear.name')->label('Année scolaire'),
            Tables\Columns\TextColumn::make('starts_at')->label('Début')->date(),
            Tables\Columns\TextColumn::make('ends_at')->label('Fin')->date(),
            Tables\Columns\TextColumn::make('status')->label('État')->badge()->formatStateUsing(fn (string $state): string => $state === 'closed' ? 'Clôturée' : 'Ouverte'),
        ])->actions([
            Tables\Actions\EditAction::make()->hidden(fn (Term $record): bool => $record->isClosed()),
            Tables\Actions\Action::make('validateGrades')
                ->label('Valider les notes')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (Term $record): bool => ! $record->isClosed() && auth()->user()?->isDirector())
                ->requiresConfirmation()
                ->action(fn (Term $record): Term => app(GradeCalculationService::class)->validateTerm($record)),
            Tables\Actions\Action::make('close')
                ->label('Clôturer la période')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->visible(fn (Term $record): bool => $record->status !== 'closed' && auth()->user()?->isDirector())
                ->requiresConfirmation()
                ->modalHeading('Clôturer cette période ?')
                ->modalDescription('Les notes, bulletins, présences, inscriptions et opérations financières de cette période ne pourront plus être modifiés sans procédure de réouverture contrôlée.')
                ->action(fn (Term $record): Term => app(TermClosureService::class)->close($record)),
        ])->bulkActions([]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListTerms::route('/'), 'create' => Pages\CreateTerm::route('/create'), 'edit' => Pages\EditTerm::route('/{record}/edit')];
    }
}
