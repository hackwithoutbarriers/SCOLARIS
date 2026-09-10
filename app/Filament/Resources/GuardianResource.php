<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuardianResource\Pages;
use App\Models\Guardian;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GuardianResource extends Resource
{
    protected static ?string $model = Guardian::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Personnes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('relationship'),
            Forms\Components\TextInput::make('phone')->label('Téléphone WhatsApp')->required()->maxLength(20)
                ->regex('/^(?:\+228|228|00228)?\d{8}$/')
                ->helperText('Numéro togolais, par exemple +22890000000.'),
            Forms\Components\TextInput::make('email')->email(),
            Forms\Components\Textarea::make('address'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable(),
            Tables\Columns\TextColumn::make('relationship'),
            Tables\Columns\TextColumn::make('phone')->searchable(),
            Tables\Columns\TextColumn::make('email'),
            Tables\Columns\TextColumn::make('students_count')->counts('students')->label('Élèves'),
        ])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListGuardians::route('/'), 'create' => Pages\CreateGuardian::route('/create'), 'edit' => Pages\EditGuardian::route('/{record}/edit')];
    }
}
