<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CertificatResource\Pages;
use App\Models\Attestation;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CertificatResource extends Resource
{
    protected static ?string $model = Attestation::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Gestion';

    protected static ?string $navigationLabel = 'Certificats';

    protected static ?string $modelLabel = 'certificat';

    protected static ?string $pluralModelLabel = 'certificats';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type_document', 'certificat');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Certificat de fin d’apprentissage')->schema([
                Forms\Components\TextInput::make('numero_attestation')
                    ->label('Numéro du certificat')
                    ->readOnly()
                    ->dehydrated(false)
                    ->formatStateUsing(fn (?string $state, ?Attestation $record): string => $record?->documentNumber('CERT') ?? (string) $state)
                    ->visibleOn('edit'),
                Forms\Components\Hidden::make('type_document')->default('certificat'),
                Forms\Components\TextInput::make('apprenti_nom_prenom')
                    ->label('Nom et prénom')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('photo_profil')
                    ->label('Photo de l’apprenti')
                    ->image()
                    ->avatar()
                    ->disk(config('filesystems.default'))
                    ->directory('attestations')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(2048)
                    ->imageEditor()
                    ->imageEditorAspectRatios(['1:1'])
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeMode('force')
                    ->imageResizeTargetWidth(600)
                    ->imageResizeTargetHeight(600),
                Forms\Components\Grid::make(['default' => 1, 'md' => 3])->schema([
                    Forms\Components\DatePicker::make('date_naissance')
                        ->label('Date de naissance')
                        ->required(),
                    Forms\Components\TextInput::make('lieu_naissance')
                        ->label('Lieu de naissance')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('nationalite')
                        ->label('Nationalité')
                        ->default('Togolaise')
                        ->required()
                        ->maxLength(255),
                ]),
                Forms\Components\Grid::make(['default' => 1, 'md' => 3])->schema([
                    Forms\Components\DatePicker::make('date_debut_apprentissage')
                        ->label('Début de l’apprentissage')
                        ->required(),
                    Forms\Components\DatePicker::make('date_fin_apprentissage')
                        ->label('Fin de l’apprentissage')
                        ->required(),
                    Forms\Components\DatePicker::make('date_delivrance')
                        ->label('Date de délivrance')
                        ->required(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_profil')
                    ->label('Photo')
                    ->disk(config('filesystems.default'))
                    ->circular(),
                Tables\Columns\TextColumn::make('numero_attestation')
                    ->label('N° certificat')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('apprenti_nom_prenom')
                    ->label('Apprenti')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_delivrance')
                    ->label('Délivré le')
                    ->date()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('voir_pdf')
                    ->label('Voir le PDF')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Attestation $record): string => route('certificat.pdf', ['attestation' => $record]))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListCertificats::route('/'),
            'edit' => Pages\EditCertificat::route('/{record}/edit'),
        ];
    }
}
