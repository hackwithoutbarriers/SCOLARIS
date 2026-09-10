<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeeStructureResource\Pages;
use App\Models\AcademicYear;
use App\Models\FeeStructure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FeeStructureResource extends Resource
{
    protected static ?string $model = FeeStructure::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finances';

    protected static ?string $navigationLabel = 'Grilles tarifaires';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('academic_year_id')->label('Année scolaire')->options(fn () => AcademicYear::query()->pluck('name', 'id'))->required()->searchable(),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Textarea::make('description'),
            Forms\Components\Repeater::make('fees')->relationship()->schema([
                Forms\Components\TextInput::make('name')->required(),
                Forms\Components\TextInput::make('amount')->numeric()->integer()->minValue(1)->required(),
                Forms\Components\Select::make('type')->label('Type')->options(['registration' => 'Inscription', 'tuition' => 'Scolarité', 'uniform' => 'Uniforme', 'canteen' => 'Cantine', 'transport' => 'Transport', 'exam' => 'Examen', 'other' => 'Autre'])->default('tuition')->required(),
                Forms\Components\DatePicker::make('due_date'),
                Forms\Components\Toggle::make('mandatory')->default(true),
                Forms\Components\Toggle::make('active')->default(true),
            ])->columns(2)->columnSpanFull(),
            Forms\Components\Toggle::make('active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable()->weight('bold')->wrap(),
            Tables\Columns\TextColumn::make('academicYear.name')->label('Année')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('fees_sum_amount')->label('Total')->money('XOF')->weight('bold'),
            Tables\Columns\IconColumn::make('active')->boolean()->toggleable(isToggledHiddenByDefault: true),
        ])->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListFeeStructures::route('/'), 'create' => Pages\CreateFeeStructure::route('/create'), 'edit' => Pages\EditFeeStructure::route('/{record}/edit')];
    }
}
