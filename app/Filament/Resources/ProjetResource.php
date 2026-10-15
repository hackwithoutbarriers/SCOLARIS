<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjetResource\Pages;
use App\Models\Projet;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProjetResource extends Resource
{
    protected static ?string $model = Projet::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Gestion';

    protected static ?string $navigationLabel = 'Projets';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Projet')->schema([
                    Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                        Forms\Components\TextInput::make('titre')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('categorie')
                            ->label('Catégorie')
                            ->options([
                                'batiment' => 'Bâtiment',
                                'mobilier' => 'Mobilier',
                            ])
                            ->required()
                            ->native(false),
                    ]),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(6)
                        ->columnSpanFull(),

                    Forms\Components\Grid::make(['default' => 1, 'md' => 3])->schema([
                        Forms\Components\TextInput::make('ville')
                            ->label('Ville')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('pays')
                            ->label('Pays')
                            ->default('Togo')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_visible_public')
                            ->label('Visible publiquement')
                            ->default(false),
                    ]),

                    Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                        Forms\Components\TextInput::make('code_suivi_diaspora')
                            ->label('Code de suivi diaspora')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\FileUpload::make('images')
                            ->label('Photos du projet')
                            ->disk(config('filesystems.default'))
                            ->directory('projets')
                            ->multiple()
                            ->image()
                            ->maxFiles(10)
                            ->reorderable()
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth(1600)
                            ->imageResizeTargetHeight(1200)
                            ->imageResizeUpscale(false)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->maxSize(2048)
                            ->columnSpanFull(),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titre')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('categorie')
                    ->label('Catégorie')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('ville')
                    ->label('Ville')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_visible_public')
                    ->label('Publication')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code_suivi_diaspora')
                    ->label('Code Diaspora')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('categorie')
                    ->label('Catégorie')
                    ->options([
                        'batiment' => 'Bâtiment',
                        'mobilier' => 'Mobilier',
                    ]),
                SelectFilter::make('ville')
                    ->label('Ville')
                    ->options(Projet::query()->pluck('ville', 'ville')->unique()->sort()->toArray()),
                SelectFilter::make('is_visible_public')
                    ->label('Statut de publication')
                    ->options([
                        '1' => 'Publié',
                        '0' => 'Masqué',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListProjets::route('/'),
            'create' => Pages\CreateProjet::route('/create'),
            'edit' => Pages\EditProjet::route('/{record}/edit'),
        ];
    }
}
