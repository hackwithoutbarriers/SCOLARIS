<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttestationResource\Pages;
use App\Models\Attestation;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AttestationResource extends Resource
{
    protected static ?string $model = Attestation::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Gestion';

    protected static ?string $navigationLabel = 'Attestations de travail';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Document')->schema([
                    Forms\Components\Select::make('document_mode')
                        ->label('Nature de la demande')
                        ->options([
                            'nouveau' => 'Nouveau document',
                            'duplicata' => 'Duplicata',
                        ])
                        ->default('nouveau')
                        ->live()
                        ->dehydrated(false)
                        ->required()
                        ->visibleOn('create'),
                    Forms\Components\Select::make('source_document_id')
                        ->label('Document original')
                        ->options(fn (): array => Attestation::query()
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (Attestation $record): array => [
                                $record->id => $record->apprenti_nom_prenom.' — '.$record->documentNumber('CERT'),
                            ])
                            ->all())
                        ->searchable()
                        ->live()
                        ->dehydrated(false)
                        ->required(fn (Get $get): bool => $get('document_mode') === 'duplicata')
                        ->visible(fn (Get $get): bool => $get('document_mode') === 'duplicata'),
                    Forms\Components\TextInput::make('numero_attestation')
                        ->label('Numéro de série')
                        ->readOnly()
                        ->dehydrated(false)
                        ->visibleOn('edit'),
                    Forms\Components\Hidden::make('type_document')->default('certificat'),
                    Forms\Components\TextInput::make('apprenti_nom_prenom')
                        ->label('Nom et prénom')
                        ->required()
                        ->visible(fn (Get $get): bool => $get('document_mode') !== 'duplicata')
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
                        ->imageResizeTargetHeight(600)
                        ->visible(fn (Get $get): bool => $get('document_mode') !== 'duplicata'),
                    Forms\Components\Grid::make(['default' => 1, 'md' => 3])->schema([
                        Forms\Components\DatePicker::make('date_naissance')
                            ->label('Date de naissance')
                            ->visible(fn (Get $get): bool => $get('document_mode') !== 'duplicata')
                            ->required(fn (Get $get): bool => $get('document_mode') !== 'duplicata'),
                        Forms\Components\TextInput::make('lieu_naissance')
                            ->label('Lieu de naissance')
                            ->visible(fn (Get $get): bool => $get('document_mode') !== 'duplicata')
                            ->required(fn (Get $get): bool => $get('document_mode') !== 'duplicata')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nationalite')
                            ->label('Nationalité')
                            ->default('Togolaise')
                            ->visible(fn (Get $get): bool => $get('document_mode') !== 'duplicata')
                            ->required(fn (Get $get): bool => $get('document_mode') !== 'duplicata')
                            ->maxLength(255),
                    ]),
                    Forms\Components\Grid::make(['default' => 1, 'md' => 2])->schema([
                        Forms\Components\DatePicker::make('date_debut_apprentissage')
                            ->label('Date de début de travail / apprentissage')
                            ->visible(fn (Get $get): bool => $get('document_mode') !== 'duplicata')
                            ->required(fn (Get $get): bool => $get('document_mode') !== 'duplicata'),
                        Forms\Components\DatePicker::make('date_fin_apprentissage')
                            ->label('Date de fin de travail / apprentissage')
                            ->visible(fn (Get $get): bool => $get('document_mode') !== 'duplicata')
                            ->required(fn (Get $get): bool => $get('document_mode') !== 'duplicata'),
                    ]),
                    Forms\Components\DatePicker::make('date_delivrance')
                        ->label('Date de délivrance')
                        ->visible(fn (Get $get): bool => $get('document_mode') !== 'duplicata')
                        ->required(fn (Get $get): bool => $get('document_mode') !== 'duplicata'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_attestation')
                    ->label('N° série')
                    ->formatStateUsing(fn (?string $state, Attestation $record): string => $record->serialNumber())
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('apprenti_nom_prenom')
                    ->label('Apprenti')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type_document')
                    ->label('Documents')
                    ->formatStateUsing(fn (?string $state): string => $state === 'certificat' ? 'Certificat + attestation' : 'Attestation'),
                Tables\Columns\TextColumn::make('date_debut_apprentissage')
                    ->label('Début')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_fin_apprentissage')
                    ->label('Fin')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_delivrance')
                    ->label('Délivrée le')
                    ->date()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('generer_documents')
                    ->label('Générer les documents')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Attestation $record): string => route('documents.links', ['attestation' => $record]))
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
            'index' => Pages\ListAttestations::route('/'),
            'create' => Pages\CreateAttestation::route('/create'),
            'edit' => Pages\EditAttestation::route('/{record}/edit'),
        ];
    }
}
