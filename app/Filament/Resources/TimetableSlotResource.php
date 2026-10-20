<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasRoleNavigation;
use App\Filament\Resources\TimetableSlotResource\Pages;
use App\Models\TimetableSlot;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TimetableSlotResource extends Resource
{
    use HasRoleNavigation;

    protected static ?string $model = TimetableSlot::class;

    protected static ?string $navigationGroup = 'Suivi scolaire';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Emploi du temps';

    protected static ?string $modelLabel = 'créneau horaire';

    protected static ?string $pluralModelLabel = 'emploi du temps';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isDirector() === true
            || auth()->user()?->isCenseur() === true
            || auth()->user()?->hasRole(['teacher', 'secretary', 'accountant']) === true
            || (auth()->user()?->isSuperAdmin() === true && auth()->user()->hasActiveSchoolIntervention());
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        if (! auth()->user()?->isDirector()) {
            $query->where('status', 'published');
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isDirector() === true;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->isDirector() === true;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isDirector() === true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('academic_year_id')->relationship('academicYear', 'name')->required(),
            Forms\Components\Select::make('teacher_id')->relationship('teacher', 'name')->required(),
            Forms\Components\Select::make('class_room_id')->relationship('classRoom', 'name')->required(),
            Forms\Components\Select::make('subject_id')->relationship('subject', 'name')->required(),
            Forms\Components\Select::make('day_of_week')->options([1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'])->required(),
            Forms\Components\TimePicker::make('starts_at')->required(), Forms\Components\TimePicker::make('ends_at')->required(),
            Forms\Components\TextInput::make('room')->label('Salle'), Forms\Components\Textarea::make('notes'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('day_of_week')->label('Jour'), Tables\Columns\TextColumn::make('starts_at')->label('Début'), Tables\Columns\TextColumn::make('ends_at')->label('Fin'), Tables\Columns\TextColumn::make('classRoom.name')->label('Classe'), Tables\Columns\TextColumn::make('subject.name')->label('Matière'), Tables\Columns\TextColumn::make('teacher.name')->label('Enseignant'), Tables\Columns\TextColumn::make('room')->label('Salle'), Tables\Columns\TextColumn::make('status')->label('État')->badge()->formatStateUsing(fn (string $state): string => $state === 'published' ? 'Publié' : 'Brouillon')])->actions([
            Tables\Actions\EditAction::make()->visible(fn (): bool => static::canEdit(null)),
            Tables\Actions\Action::make('publish')->label('Publier')->color('success')->requiresConfirmation()->visible(fn (TimetableSlot $record): bool => auth()->user()?->isDirector() === true && $record->status === 'draft')->action(fn (TimetableSlot $record) => $record->update(['status' => 'published', 'published_by' => auth()->id(), 'published_at' => now()])),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListTimetableSlots::route('/'), 'create' => Pages\CreateTimetableSlot::route('/create'), 'edit' => Pages\EditTimetableSlot::route('/{record}/edit')];
    }
}
