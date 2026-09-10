<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConductLabelResource\Pages;
use App\Models\ConductLabel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConductLabelResource extends Resource
{
    protected static ?string $model = ConductLabel::class;
    protected static ?string $navigationGroup = 'Paramétrage scolaire';
    protected static ?string $navigationLabel = 'Libellés de conduite';
    protected static ?string $navigationIcon = 'heroicon-o-hand-thumb-up';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('label')->required(),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0)->required(),
            Forms\Components\Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('label')->searchable(),
            Tables\Columns\TextColumn::make('sort_order'),
            Tables\Columns\IconColumn::make('active')->boolean(),
        ])->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConductLabels::route('/'),
            'create' => Pages\CreateConductLabel::route('/create'),
            'edit' => Pages\EditConductLabel::route('/{record}/edit'),
        ];
    }
}
