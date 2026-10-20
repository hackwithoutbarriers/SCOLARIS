<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasRoleNavigation;
use App\Filament\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use App\Services\CertificateService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CertificateResource extends Resource
{
    use HasRoleNavigation;

    protected static ?string $model = Certificate::class;

    protected static ?string $navigationGroup = 'Suivi scolaire';

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationLabel = 'Certificats';

    protected static ?string $modelLabel = 'certificat';

    protected static ?string $pluralModelLabel = 'certificats';

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSecretary() === true || auth()->user()?->isDirector() === true || (auth()->user()?->isSuperAdmin() === true && auth()->user()->hasActiveSchoolIntervention());
    }

    public static function getCreateButtonLabel(): string
    {
        return 'Demander un certificat';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSecretary() === true || auth()->user()?->isDirector() === true || (auth()->user()?->isSuperAdmin() === true && auth()->user()->hasActiveSchoolIntervention());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('student_id')->label('Élève')->relationship('student', 'last_name')->getOptionLabelFromRecordUsing(fn ($record): string => trim($record->first_name.' '.$record->last_name).' — '.$record->student_number)->searchable(['first_name', 'last_name', 'student_number'])->preload()->required(),
            Forms\Components\Select::make('document_type')->label('Type de document')->options(['certificate' => 'Certificat de scolarité', 'attestation' => 'Attestation de fréquentation', 'radiation' => 'Certificat de radiation'])->required(),
            Forms\Components\Textarea::make('workflow_note')->label('Remarque')->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('number')->label('Numéro')->searchable()->placeholder('Non émis'), Tables\Columns\TextColumn::make('document_type')->label('Type')->formatStateUsing(fn (string $state): string => ['certificate' => 'Certificat de scolarité', 'attestation' => 'Attestation de fréquentation', 'radiation' => 'Certificat de radiation'][$state] ?? $state), Tables\Columns\TextColumn::make('student.full_name')->label('Élève')->searchable(), Tables\Columns\TextColumn::make('status')->label('État')->badge()->formatStateUsing(fn (string $state): string => ['draft' => 'Brouillon', 'pending_validation' => 'En attente', 'validated' => 'Validé', 'delivered' => 'Remis', 'cancelled' => 'Annulé'][$state] ?? $state), Tables\Columns\TextColumn::make('created_at')->label('Demandé le')->dateTime('d/m/Y H:i')])->actions([
            Tables\Actions\EditAction::make()->label('Modifier')->visible(fn (Certificate $record): bool => $record->status === 'draft' && (auth()->user()?->isSecretary() || auth()->user()?->isDirector())),
            Tables\Actions\Action::make('submit')->label('Soumettre')->visible(fn (Certificate $record): bool => auth()->user()?->isSecretary() === true && $record->status === 'draft')->action(fn (Certificate $record) => app(CertificateService::class)->submit($record)),
            Tables\Actions\Action::make('validate')->label('Valider')->color('success')->visible(fn (Certificate $record): bool => auth()->user()?->isDirector() === true && in_array($record->status, ['draft', 'pending_validation'], true))->action(fn (Certificate $record) => app(CertificateService::class)->validate($record)),
            Tables\Actions\Action::make('deliver')->label('Remettre')->visible(fn (Certificate $record): bool => in_array($record->status, ['validated'], true) && (auth()->user()?->isSecretary() || auth()->user()?->isDirector()))->form([Forms\Components\Select::make('delivery_method')->label('Mode')->options(['school' => 'Retrait à l’école', 'whatsapp' => 'WhatsApp', 'email' => 'E-mail'])->required(), Forms\Components\TextInput::make('delivered_to')->label('Destinataire')->required()])->action(fn (Certificate $record, array $data) => app(CertificateService::class)->deliver($record, $data['delivery_method'], $data['delivered_to'])),
            Tables\Actions\Action::make('cancel')->label('Annuler')->color('danger')->visible(fn (Certificate $record): bool => auth()->user()?->isDirector() === true && in_array($record->status, ['draft', 'pending_validation', 'validated'], true))->form([Forms\Components\Textarea::make('reason')->label('Motif obligatoire')->required()->minLength(10)])->action(fn (Certificate $record, array $data) => app(CertificateService::class)->cancel($record, $data['reason'])),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCertificates::route('/'), 'create' => Pages\CreateCertificate::route('/create'), 'edit' => Pages\EditCertificate::route('/{record}/edit')];
    }
}
