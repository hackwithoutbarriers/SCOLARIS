<?php

namespace App\Filament\Resources;

use App\Filament\Pages\StudentImport;
use App\Filament\Resources\StudentResource\Pages;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Élèves et familles';

    protected static ?string $navigationLabel = 'Élèves';

    protected static ?string $modelLabel = 'élève';

    protected static ?string $pluralModelLabel = 'élèves';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isDirector() === true
            || auth()->user()?->isSecretary() === true
            || (auth()->user()?->isSuperAdmin() === true && auth()->user()->hasActiveSchoolIntervention());
    }

    public static function getCreateButtonLabel(): string
    {
        return 'Inscrire un élève';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Élève')->schema([
                Forms\Components\TextInput::make('first_name')->label('Prénom')->required(),
                Forms\Components\TextInput::make('last_name')->label('Nom')->required(),
                Forms\Components\TextInput::make('middle_name')->label('Deuxième prénom'),
                Forms\Components\DatePicker::make('date_of_birth')->label('Date de naissance')->required(),
                Forms\Components\TextInput::make('place_of_birth')->label('Lieu de naissance'),
                Forms\Components\Select::make('gender')->label('Sexe')->options(['female' => 'Féminin', 'male' => 'Masculin', 'other' => 'Autre'])->required(),
                Forms\Components\Select::make('status')->label('Statut')->options(['active' => 'Actif', 'inactive' => 'Inactif'])->default('active')->required(),
                Forms\Components\FileUpload::make('photo_path')->label('Photo')->image()->disk('public')->directory('students')->nullable(),
                Forms\Components\Textarea::make('address')->label('Adresse')->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make('Inscription scolaire')->visibleOn('create')->schema([
                Forms\Components\Select::make('enrollment_academic_year_id')
                    ->label('Année scolaire')
                    ->options(fn (): array => AcademicYear::query()->orderByDesc('is_current')->orderByDesc('start_date')->pluck('name', 'id')->all())
                    ->default(fn (): ?int => AcademicYear::query()->where('is_current', true)->value('id'))
                    ->live()
                    ->required(),
                Forms\Components\Select::make('enrollment_class_room_id')
                    ->label('Classe')
                    ->options(function (Get $get): array {
                        $yearId = $get('enrollment_academic_year_id');
                        if (! $yearId) {
                            return [];
                        }

                        return ClassRoom::query()->where('academic_year_id', $yearId)
                            ->orderBy('cycle')->orderBy('grade_level')->orderBy('name')
                            ->pluck('name', 'id')->all();
                    })
                    ->disabled(fn (Get $get): bool => ! $get('enrollment_academic_year_id'))
                    ->required(),
                Forms\Components\DatePicker::make('enrollment_date')->label('Date d’inscription')->default(now())->required(),
            ])->columns(2),
            Forms\Components\Section::make('Tuteur principal')->visibleOn('create')->schema([
                Forms\Components\TextInput::make('guardian_first_name')->label('Prénom')->required(),
                Forms\Components\TextInput::make('guardian_last_name')->label('Nom')->required(),
                Forms\Components\TextInput::make('guardian_relationship')->label('Lien avec l’élève')->required(),
                Forms\Components\TextInput::make('guardian_phone')->label('Téléphone / WhatsApp')->required(),
                Forms\Components\TextInput::make('guardian_email')->label('E-mail')->email(),
                Forms\Components\Textarea::make('guardian_address')->label('Adresse')->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make('Second tuteur (facultatif)')->visibleOn('create')->schema([
                Forms\Components\TextInput::make('second_guardian_first_name')->label('Prénom'),
                Forms\Components\TextInput::make('second_guardian_last_name')->label('Nom'),
                Forms\Components\TextInput::make('second_guardian_relationship')->label('Lien avec l’élève'),
                Forms\Components\TextInput::make('second_guardian_phone')->label('Téléphone / WhatsApp'),
                Forms\Components\TextInput::make('second_guardian_email')->label('E-mail')->email(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('full_name')->label('Nom complet')->searchable(['first_name', 'last_name']),
            Tables\Columns\TextColumn::make('currentEnrollment.classRoom.name')->label('Classe')->toggleable(),
            Tables\Columns\TextColumn::make('primary_guardian_phone')->label('Téléphone du tuteur')->toggleable(),
            Tables\Columns\BadgeColumn::make('status')->colors(['success' => 'active', 'danger' => 'inactive']),
        ])->headerActions([
            Tables\Actions\Action::make('importStudents')->label('Importer des élèves')->url(StudentImport::getUrl()),
            Tables\Actions\Action::make('exportCsv')->label('Exporter CSV')->url(fn (): string => route('students.registry.csv'))->openUrlInNewTab(),
            Tables\Actions\Action::make('exportPdf')->label('Exporter PDF')->url(fn (): string => route('students.registry.pdf'))->openUrlInNewTab(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListStudents::route('/'), 'create' => Pages\CreateStudent::route('/create'), 'edit' => Pages\EditStudent::route('/{record}/edit')];
    }
}
