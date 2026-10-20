<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasRoleNavigation;
use App\Filament\Resources\AcademicYearResource\Pages;
use App\Models\AcademicYear;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class AcademicYearResource extends Resource
{
    use HasRoleNavigation;

    protected static ?string $model = AcademicYear::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Paramétrage scolaire';

    protected static ?string $navigationLabel = 'Années scolaires';

    protected static ?string $modelLabel = 'année scolaire';

    protected static ?string $pluralModelLabel = 'années scolaires';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Libellé de l’année scolaire')
                ->default(fn (): string => now()->year.'-'.(now()->year + 1))
                ->placeholder('2026-2027')
                ->required(),
            Forms\Components\DatePicker::make('start_date')
                ->label('Date de début')
                ->default(fn (): Carbon => Carbon::create(now()->year, 9, 1))
                ->required(),
            Forms\Components\DatePicker::make('end_date')
                ->label('Date de fin')
                ->default(fn (): Carbon => Carbon::create(now()->year + 1, 7, 31))
                ->after('start_date')
                ->required(),
            Forms\Components\Select::make('status')->label('État')->options([
                'draft' => 'Préparation',
                'active' => 'Active',
                'closed' => 'Clôturée',
            ])->default('draft')->required(),
            Forms\Components\Toggle::make('is_current')->label('Définir comme année scolaire courante')
                ->helperText('Une seule année peut être courante pour l’établissement.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('start_date')->label('Début')->date(),
            Tables\Columns\TextColumn::make('end_date')->label('Fin')->date(),
            Tables\Columns\TextColumn::make('status')->label('État')->badge(),
            Tables\Columns\IconColumn::make('is_current')->label('Courante')->boolean(),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAcademicYears::route('/'), 'create' => Pages\CreateAcademicYear::route('/create'), 'edit' => Pages\EditAcademicYear::route('/{record}/edit')];
    }
}
