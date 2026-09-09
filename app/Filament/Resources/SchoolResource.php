<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchoolResource\Pages;
use App\Models\School;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\EditAction;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;
    protected static ?string $navigationGroup = 'SaaS';
    protected static ?string $navigationLabel = 'Écoles';
    public static function canAccess(): bool { return auth()->user()?->isSuperAdmin() ?? false; }
    public static function form(Form $form): Form { return $form->schema([
        TextInput::make('name')->required(), TextInput::make('code')->required()->unique(ignoreRecord: true),
        TextInput::make('email')->email(), TextInput::make('phone'), TextInput::make('city'),
        TextInput::make('country')->default('Togo')->required(), Toggle::make('active')->default(true),
    ]); }
    public static function table(Table $table): Table { return $table->columns([
        TextColumn::make('name')->searchable()->sortable(), TextColumn::make('code')->searchable(),
        TextColumn::make('city'), IconColumn::make('active')->boolean(),
    ])->actions([EditAction::make()])->defaultPaginationPageOption(25); }
    public static function getPages(): array { return ['index' => Pages\ListSchools::route('/'), 'create' => Pages\CreateSchool::route('/create'), 'edit' => Pages\EditSchool::route('/{record}/edit')]; }
}
