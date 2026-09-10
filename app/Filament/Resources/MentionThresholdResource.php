<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MentionThresholdResource\Pages;
use App\Models\MentionThreshold;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MentionThresholdResource extends Resource
{
    protected static ?string $model = MentionThreshold::class;
    protected static ?string $navigationGroup = 'Paramétrage scolaire';
    protected static ?string $navigationLabel = 'Mentions académiques';
    protected static ?string $navigationIcon = 'heroicon-o-star';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('academic_year_id')->relationship('academicYear', 'name')->required()->preload(),
            Forms\Components\TextInput::make('label')->required(),
            Forms\Components\TextInput::make('minimum_score')->numeric()->minValue(0)->maxValue(100)->required(),
            Forms\Components\TextInput::make('maximum_score')->numeric()->minValue(0)->maxValue(100),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0)->required(),
            Forms\Components\Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('academicYear.name')->label('Année'),
            Tables\Columns\TextColumn::make('label')->searchable(),
            Tables\Columns\TextColumn::make('minimum_score')->label('Minimum'),
            Tables\Columns\TextColumn::make('maximum_score')->label('Maximum'),
            Tables\Columns\IconColumn::make('active')->boolean(),
        ])->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMentionThresholds::route('/'),
            'create' => Pages\CreateMentionThreshold::route('/create'),
            'edit' => Pages\EditMentionThreshold::route('/{record}/edit'),
        ];
    }
}
