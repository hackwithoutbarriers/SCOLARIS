<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssessmentResource\Pages;
use App\Models\Assessment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AssessmentResource extends Resource
{
    protected static ?string $model = Assessment::class;

    protected static ?string $navigationGroup = 'Scolarité';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('subject_config_id')->relationship('subjectConfig', 'id')->required()->searchable()->preload(),
            Forms\Components\Select::make('term_id')->relationship('term', 'name')->required()->preload(),
            Forms\Components\Select::make('teacher_id')->relationship('teacher', 'name')->searchable()->preload(),
            Forms\Components\TextInput::make('title')->required()->maxLength(255),
            Forms\Components\DatePicker::make('assessment_date'),
            Forms\Components\TextInput::make('max_score')->numeric()->minValue(0.01)->required(),
            Forms\Components\TextInput::make('weight')->numeric()->minValue(0)->required(),
            Forms\Components\Select::make('status')->label('Statut')->options(['draft' => 'Brouillon', 'published' => 'Publié'])->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('title')->searchable(), Tables\Columns\TextColumn::make('subjectConfig.subject.name'), Tables\Columns\TextColumn::make('term.name'), Tables\Columns\TextColumn::make('status')->badge(), Tables\Columns\TextColumn::make('assessment_date')->date()])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAssessments::route('/'), 'create' => Pages\CreateAssessment::route('/create'), 'edit' => Pages\EditAssessment::route('/{record}/edit')];
    }
}
