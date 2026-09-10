<?php
namespace App\Filament\Resources;
use App\Filament\Resources\CertificateResource\Pages;
use App\Models\Certificate;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class CertificateResource extends Resource {
    protected static ?string $model = Certificate::class;
    protected static ?string $navigationGroup = 'Scolarité';
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    public static function table(Table $table): Table { return $table->columns([Tables\Columns\TextColumn::make('number')->searchable(),Tables\Columns\TextColumn::make('document_type')->label('Type'),Tables\Columns\TextColumn::make('student.full_name')->label('Élève'),Tables\Columns\TextColumn::make('created_at')->dateTime()])->actions([]); }
    public static function getPages(): array { return ['index'=>Pages\ListCertificates::route('/')]; }
}
