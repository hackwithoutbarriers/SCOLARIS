<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubjectConfigResource\Pages;
use App\Models\SubjectConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubjectConfigResource extends Resource
{
    protected static ?string $model = SubjectConfig::class;

    protected static ?string $navigationGroup = 'Paramétrage scolaire';

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('subject_id')->relationship('subject', 'name')->required()->searchable()->preload(),
            Forms\Components\Select::make('academic_year_id')->relationship('academicYear', 'name')->required()->preload(),
            Forms\Components\Select::make('class_room_id')->relationship('classRoom', 'name')->searchable()->preload(),
            Forms\Components\Select::make('grading_method')->options(['weighted_average' => 'Weighted average', 'simple_average' => 'Simple average'])->required(),
            Forms\Components\TextInput::make('passing_score')->numeric()->minValue(0)->maxValue(100)->required(),
            Forms\Components\TextInput::make('max_score')->numeric()->minValue(0.01)->required(),
            Forms\Components\TextInput::make('weight')->numeric()->minValue(0)->required(),
            Forms\Components\KeyValue::make('rules')->label('Règles (JSON)'),
            Forms\Components\Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('subject.name')->searchable(),
            Tables\Columns\TextColumn::make('academicYear.name'),
            Tables\Columns\TextColumn::make('grading_method'),
            Tables\Columns\TextColumn::make('passing_score'),
            Tables\Columns\IconColumn::make('active')->boolean(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSubjectConfigs::route('/'), 'create' => Pages\CreateSubjectConfig::route('/create'), 'edit' => Pages\EditSubjectConfig::route('/{record}/edit')];
    }
}
