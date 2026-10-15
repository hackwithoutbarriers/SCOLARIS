<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TemoignageResource\Pages;
use App\Models\Temoignage;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TemoignageResource extends Resource
{
    protected static ?string $model = Temoignage::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Gestion';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Témoignage client')->schema([
                    Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                        Forms\Components\Select::make('devis_id')
                            ->label('Devis lié')
                            ->relationship('devis', 'numero_devis')
                            ->searchable()
                            ->preload()
                            ->placeholder('Aucun devis lié'),
                        Forms\Components\TextInput::make('projet_ref')
                            ->label('Référence du projet')
                            ->maxLength(255)
                            ->placeholder('devis_0187'),
                    ]),

                    Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                        Forms\Components\TextInput::make('nom_client')
                            ->label('Nom du client')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ville')
                            ->label('Ville')
                            ->maxLength(255),
                    ]),

                    Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                        Forms\Components\TextInput::make('projet_type')
                            ->label('Type de projet')
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('date_projet')
                            ->label('Date du projet'),
                    ]),

                    Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                        Forms\Components\Select::make('note')
                            ->label('Note')
                            ->options([
                                1 => '1 étoile',
                                2 => '2 étoiles',
                                3 => '3 étoiles',
                                4 => '4 étoiles',
                                5 => '5 étoiles',
                            ])
                            ->default(5)
                            ->required(),
                        Forms\Components\Select::make('source')
                            ->label('Source')
                            ->options([
                                'interne' => 'Interne',
                                'google' => 'Google',
                            ])
                            ->default('interne')
                            ->required(),
                    ]),

                    Forms\Components\Textarea::make('texte')
                        ->label('Avis du client')
                        ->required()
                        ->rows(6)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('photo_projet')
                        ->label('Photo du projet')
                        ->helperText('Chemin/public depuis le disque public, ex. /uploads/temoignages/tm_042.jpg')
                        ->maxLength(255),

                    Toggle::make('consentement')
                        ->label('Consentement explicite du client')
                        ->default(false)
                        ->required(),

                    Forms\Components\Select::make('statut')
                        ->label('Statut')
                        ->options([
                            'brouillon' => 'Brouillon',
                            'en_attente' => 'En attente',
                            'publie' => 'Publié',
                        ])
                        ->default('en_attente')
                        ->required(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom_client')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ville')
                    ->label('Ville')
                    ->searchable(),
                Tables\Columns\TextColumn::make('projet_ref')
                    ->label('Projet')
                    ->searchable(),
                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->suffix('★')
                    ->sortable(),
                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'brouillon' => 'gray',
                        'en_attente' => 'warning',
                        'publie' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'brouillon' => 'Brouillon',
                        'en_attente' => 'En attente',
                        'publie' => 'Publié',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('consentement')
                    ->label('Consentement')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Oui' : 'Non')
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->label('Statut')
                    ->options([
                        'brouillon' => 'Brouillon',
                        'en_attente' => 'En attente',
                        'publie' => 'Publié',
                    ]),
                SelectFilter::make('source')
                    ->label('Source')
                    ->options([
                        'interne' => 'Interne',
                        'google' => 'Google',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTemoignages::route('/'),
            'create' => Pages\CreateTemoignage::route('/create'),
            'edit' => Pages\EditTemoignage::route('/{record}/edit'),
        ];
    }
}
