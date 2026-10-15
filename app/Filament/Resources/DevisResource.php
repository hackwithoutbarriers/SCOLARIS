<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DevisResource\Pages;
use App\Models\Devis;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DevisResource extends Resource
{
    protected static ?string $model = Devis::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Gestion';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Client')
                    ->description('Coordonnées du client et localisation du chantier.')
                    ->columns(1)
                    ->schema([
                        Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                            Forms\Components\TextInput::make('client_nom')
                                ->label('Nom du client')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('client_telephone')
                                ->label('Téléphone')
                                ->tel()
                                ->required()
                                ->maxLength(255),
                        ]),
                        Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                            Forms\Components\TextInput::make('client_ville')
                                ->label('Ville du client')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('client_pays')
                                ->label('Pays du client')
                                ->default('Togo')
                                ->required()
                                ->maxLength(255),
                        ]),
                    ]),

                Section::make('Devis')
                    ->description('Détaillez les prestations, les montants et les conditions commerciales.')
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('numero_devis')
                            ->label('Numéro du devis')
                            ->readOnly()
                            ->dehydrated(false)
                            ->visibleOn('edit'),

                        Forms\Components\Textarea::make('description_chantier')
                            ->label('Description du chantier')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('lignes_facturation')
                            ->label('Lignes de facturation')
                            ->itemLabel(fn (array $state): ?string => filled($state['designation'] ?? null)
                                ? (string) $state['designation']
                                : 'Nouvelle prestation')
                            ->addActionLabel('Ajouter une prestation')
                            ->schema([
                                Forms\Components\TextInput::make('designation')
                                    ->label('Titre de la prestation')
                                    ->required(),
                                Forms\Components\Textarea::make('description')
                                    ->label('Description / mesures')
                                    ->rows(2)
                                    ->placeholder('Ex. L 1,20 m x H 2,10 m, vitrage double...')
                                    ->columnSpan(['default' => 1, 'sm' => 2, 'xl' => 2]),
                                Forms\Components\TextInput::make('quantite')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::updateLineTotals($get, $set)),
                                Forms\Components\TextInput::make('prix_unitaire')
                                    ->label('Prix unitaire')
                                    ->numeric()
                                    ->required()
                                    ->prefix('FCFA ')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::updateLineTotals($get, $set)),
                                Forms\Components\TextInput::make('total')
                                    ->label('Total')
                                    ->numeric()
                                    ->prefix('FCFA ')
                                    ->disabled()
                                    ->dehydrated()
                                    ->formatStateUsing(fn ($state, Forms\Get $get): float => (float) ($get('quantite') ?? 0) * (float) ($get('prix_unitaire') ?? 0)),
                            ])
                            ->columns(['default' => 1, 'sm' => 2, 'xl' => 5])
                            ->defaultItems(1)
                            ->live()
                            ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::updateGrandTotals($get, $set))
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                            Forms\Components\TextInput::make('total_prestations')
                                ->label('Total prestations')
                                ->numeric()
                                ->prefix('FCFA ')
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('montant_main_doeuvre')
                                ->label('Main-d’œuvre')
                                ->required()
                                ->numeric()
                                ->prefix('FCFA ')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn (Forms\Get $get, Forms\Set $set) => self::updateGrandTotals($get, $set)),
                        ]),

                        Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                            Forms\Components\TextInput::make('acompte_requis_pourcentage')
                                ->label('Acompte requis (%)')
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(100),
                            Forms\Components\Select::make('statut')
                                ->label('Statut')
                                ->options([
                                    'brouillon' => 'Brouillon',
                                    'envoye' => 'Envoyé',
                                    'accepte' => 'Accepté',
                                    'livre' => 'Livré',
                                    'refuse' => 'Refusé',
                                ])
                                ->default('brouillon')
                                ->required(),
                        ]),

                        Forms\Components\TextInput::make('montant_total')
                            ->label('Total HT')
                            ->numeric()
                            ->prefix('FCFA ')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($state, $record) {
                                $value = $state ?? (($record?->montant_materiel ?? 0) + ($record?->montant_main_doeuvre ?? 0));

                                return (float) $value;
                            }),
                    ]),
            ]);
    }

    private static function updateLineTotals(Forms\Get $get, Forms\Set $set): void
    {
        $set('total', (float) ($get('quantite') ?? 0) * (float) ($get('prix_unitaire') ?? 0));
        self::updateGrandTotals($get, $set);
    }

    private static function updateGrandTotals(Forms\Get $get, Forms\Set $set): void
    {
        $lines = $get('lignes_facturation');
        $basePath = '';

        if (! is_array($lines)) {
            $lines = $get('../../lignes_facturation');
            $basePath = '../../';
        }

        $totalPrestations = collect(is_array($lines) ? $lines : [])->filter(fn (mixed $line): bool => is_array($line))->sum(
            fn (array $line): float => (float) ($line['quantite'] ?? 0) * (float) ($line['prix_unitaire'] ?? 0)
        );

        $set($basePath.'total_prestations', $totalPrestations);
        $set($basePath.'montant_total', $totalPrestations + (float) ($get($basePath.'montant_main_doeuvre') ?? 0));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_devis')
                    ->label('N° devis')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_nom')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client_telephone')
                    ->label('Téléphone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('client_ville')
                    ->label('Ville')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('montant_total')
                    ->label('Total HT')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('acompte_requis_pourcentage')
                    ->label('Acompte')
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'brouillon' => 'gray',
                        'envoye' => 'warning',
                        'accepte' => 'info',
                        'livre' => 'success',
                        'refuse' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'brouillon' => 'Brouillon',
                        'envoye' => 'Envoyé',
                        'accepte' => 'Accepté',
                        'livre' => 'Livré',
                        'refuse' => 'Refusé',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
                SelectFilter::make('statut')
                    ->label('Statut')
                    ->options([
                        'brouillon' => 'Brouillon',
                        'envoye' => 'Envoyé',
                        'accepte' => 'Accepté',
                        'livre' => 'Livré',
                        'refuse' => 'Refusé',
                    ]),
                SelectFilter::make('client_pays')
                    ->label('Pays')
                    ->options(Devis::query()->pluck('client_pays', 'client_pays')->unique()->sort()->toArray()),
            ])
            ->actions([
                Tables\Actions\Action::make('telecharger_pdf')
                    ->label('Télécharger PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Devis $record): string => route('devis.pdf', ['devis' => $record]))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListDevis::route('/'),
            'create' => Pages\CreateDevis::route('/create'),
            'edit' => Pages\EditDevis::route('/{record}/edit'),
        ];
    }
}
