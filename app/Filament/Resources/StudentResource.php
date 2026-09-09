<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'People';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Student')->schema([
                Forms\Components\TextInput::make('admission_number')->required(),
                Forms\Components\TextInput::make('first_name')->required(),
                Forms\Components\TextInput::make('last_name')->required(),
                Forms\Components\DatePicker::make('date_of_birth'),
                Forms\Components\Select::make('gender')->options(['female' => 'Female', 'male' => 'Male', 'other' => 'Other']),
                Forms\Components\Select::make('status')->options(['active' => 'Active', 'inactive' => 'Inactive'])->default('active')->required(),
                Forms\Components\TextInput::make('email')->email(),
                Forms\Components\TextInput::make('phone'),
                Forms\Components\Textarea::make('address')->columnSpanFull(),
            ])->columns(2),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('admission_number')->searchable(),
            Tables\Columns\TextColumn::make('full_name')->label('Name')->searchable(['first_name', 'last_name']),
            Tables\Columns\TextColumn::make('email')->toggleable(),
            Tables\Columns\TextColumn::make('phone')->toggleable(),
            Tables\Columns\BadgeColumn::make('status')->colors(['success' => 'active', 'danger' => 'inactive']),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
    public static function getPages(): array
    {
        return ['index' => Pages\ListStudents::route('/'), 'create' => Pages\CreateStudent::route('/create'), 'edit' => Pages\EditStudent::route('/{record}/edit')];
    }
}
