<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasRoleNavigation;
use App\Filament\Resources\ClassRoomResource\Pages;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClassRoomResource extends Resource
{
    use HasRoleNavigation;

    protected static ?string $model = ClassRoom::class;

    protected static ?string $navigationGroup = 'Paramétrage scolaire';

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Classes';

    protected static ?string $modelLabel = 'classe';

    protected static ?string $pluralModelLabel = 'classes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('academic_year_id')->label('Année scolaire')
                ->relationship('academicYear', 'name')
                ->default(fn (): ?int => AcademicYear::query()->where('is_current', true)->value('id'))
                ->required(),
            Forms\Components\Select::make('cycle')->label('Cycle')
                ->options(function (): array {
                    $school = auth()->user()?->school;
                    $cycles = $school?->enabledEducationCycles() ?: array_keys(School::educationCycleOptions());

                    return array_intersect_key(School::educationCycleOptions(), array_flip($cycles));
                })
                ->live()
                ->afterStateUpdated(function (Set $set): void {
                    $set('grade_level', null);
                    $set('filiere', null);
                })
                ->required(),
            Forms\Components\Select::make('grade_level')->label('Niveau')
                ->options(function (Get $get): array {
                    $cycle = $get('cycle');

                    return $cycle ? (School::educationLevelOptions()[$cycle] ?? []) : [];
                })
                ->visible(fn (Get $get): bool => in_array($get('cycle'), ['college', 'lycee'], true))
                ->disabled(fn (Get $get): bool => ! $get('cycle'))->live()
                ->required(fn (Get $get): bool => in_array($get('cycle'), ['college', 'lycee'], true)),
            Forms\Components\Select::make('filiere')->label('Série')
                ->options(School::educationSeriesOptions())
                ->visible(fn (Get $get): bool => $get('cycle') === 'lycee')
                ->required(fn (Get $get): bool => $get('cycle') === 'lycee')
                ->nullable(),
            Forms\Components\TextInput::make('name')->label('Nom de la classe')
                ->helperText(fn (Get $get): string => match ($get('cycle')) {
                    'primaire' => 'Exemple : CM2 A ou Classe orange. Saisissez le nom utilisé par votre école.',
                    'college' => 'Exemple : 6e A ou 3e B.',
                    'lycee' => 'Exemple : Terminale Générale C.',
                    default => 'Choisissez d’abord le cycle, puis saisissez le nom de la classe.',
                })
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(), Tables\Columns\TextColumn::make('academicYear.name')->label('Année'),             Tables\Columns\TextColumn::make('grade_level')->label('Niveau'), Tables\Columns\TextColumn::make('cycle')->label('Cycle'), Tables\Columns\TextColumn::make('filiere')->label('Filière'), Tables\Columns\TextColumn::make('enrollments_count')->counts('enrollments')->label('Élèves'),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListClassRooms::route('/'), 'create' => Pages\CreateClassRoom::route('/create'), 'edit' => Pages\EditClassRoom::route('/{record}/edit')];
    }
}
