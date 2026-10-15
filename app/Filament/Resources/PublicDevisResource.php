<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PublicDevisResource\Pages;
use App\Models\PublicDevis;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PublicDevisResource extends Resource
{
    protected static ?string $model = PublicDevis::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Gestion';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Demande de devis')
                    ->description('Consultez les coordonnées, le besoin et le statut de la demande.')
                    ->columns(1)
                    ->schema([
                        Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                            Forms\Components\TextInput::make('numero_demande')
                                ->label('N° demande')
                                ->readOnly()
                                ->dehydrated(false),
                            Forms\Components\Select::make('statut')
                                ->label('Statut')
                                ->options([
                                    'nouvelle' => 'Nouvelle',
                                    'en_cours' => 'En cours',
                                    'convertie' => 'Convertie',
                                ])
                                ->default('nouvelle')
                                ->required(),
                        ]),

                        Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                            Forms\Components\TextInput::make('nom')
                                ->label('Nom')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('telephone')
                                ->label('Téléphone')
                                ->tel()
                                ->required()
                                ->maxLength(30),
                        ]),

                        Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                            Forms\Components\TextInput::make('ville')
                                ->label('Ville')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('pays')
                                ->label('Pays')
                                ->default('Togo')
                                ->required()
                                ->maxLength(255),
                        ]),

                        Forms\Components\Textarea::make('description_besoin')
                            ->label('Description du besoin')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_demande')
                    ->label('N° demande')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('ville')
                    ->label('Ville')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('pays')
                    ->label('Pays')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sous_type')
                    ->label('Projet')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => $state ? str_replace('-', ' ', ucfirst($state)) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('estimation_min')
                    ->label('Estimation')
                    ->placeholder('—')
                    ->formatStateUsing(fn (?int $state, PublicDevis $record): string => $state === null
                        ? '—'
                        : number_format($state, 0, ',', ' ').' — '.number_format((int) ($record->estimation_max ?? $state), 0, ',', ' ').' '.($record->devise ?? 'FCFA')),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'nouvelle' => 'info',
                        'en_cours' => 'warning',
                        'convertie' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'nouvelle' => 'Nouvelle',
                        'en_cours' => 'En cours',
                        'convertie' => 'Convertie',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->label('Statut')
                    ->options([
                        'nouvelle' => 'Nouvelle',
                        'en_cours' => 'En cours',
                        'convertie' => 'Convertie',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('convert_to_devis')
                    ->label('Convertir en devis')
                    ->icon('heroicon-o-document-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->hidden(fn (PublicDevis $record) => $record->statut === 'convertie')
                    ->action(function (PublicDevis $record) {
                        $devis = $record->convertToDevis();

                        Notification::make()
                            ->success()
                            ->title('Devis officiel créé')
                            ->body("Le devis {$devis->numero_devis} a été généré pour {$record->nom}.")
                            ->send();
                    }),
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
            'index' => Pages\ListPublicDevis::route('/'),
            'create' => Pages\CreatePublicDevis::route('/create'),
            'edit' => Pages\EditPublicDevis::route('/{record}/edit'),
        ];
    }
}
