<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GradeResource\Pages;
use App\Models\Grade;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GradeResource extends Resource
{
    protected static ?string $model = Grade::class;
    protected static ?string $navigationGroup = 'Academic';
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    public static function getNavigationBadge(): ?string { return (string) static::getModel()::count(); }
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('assessment_id')->relationship('assessment', 'title')->required()->searchable()->preload(),
            Forms\Components\Select::make('student_id')->relationship('student', 'student_number')->required()->searchable()->preload(),
            Forms\Components\TextInput::make('score')->numeric()->minValue(0)->required()->helperText('Cannot exceed the assessment maximum.'),
            Forms\Components\Textarea::make('remarks'),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('assessment.title')->searchable()->wrap(),
            Tables\Columns\TextColumn::make('student.full_name')->searchable()->wrap(),
            Tables\Columns\TextInputColumn::make('score')->rules(['numeric', 'min:0'])->extraAttributes(['class' => 'min-w-20']),
            Tables\Columns\TextColumn::make('normalized_score')->label('%')->toggleable(),
            Tables\Columns\TextColumn::make('grade_letter')->badge()->toggleable(),
            Tables\Columns\TextColumn::make('validated_at')->dateTime()->toggleable(),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
    public static function getPages(): array { return ['index' => Pages\ListGrades::route('/'), 'create' => Pages\CreateGrade::route('/create'), 'edit' => Pages\EditGrade::route('/{record}/edit')]; }
}
