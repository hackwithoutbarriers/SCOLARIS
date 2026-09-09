<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportCardTemplateResource\Pages;
use App\Models\ReportCardTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReportCardTemplateResource extends Resource
{
    protected static ?string $model = ReportCardTemplate::class;
    protected static ?string $navigationGroup = 'Academic';
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('version')->numeric()->minValue(1)->default(1)->required(),
            Forms\Components\Select::make('status')->options(['DRAFT' => 'Draft', 'ACTIVE' => 'Active', 'ARCHIVED' => 'Archived'])->default('DRAFT')->required(),
            Forms\Components\Select::make('orientation')->options(['portrait' => 'Portrait', 'landscape' => 'Landscape'])->default('portrait')->required(),
            Forms\Components\Textarea::make('schema')->label('Template configuration (JSON)')->required()
                ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($state ?: json_encode(\App\Models\ReportCardTemplate::defaultSchema(), JSON_PRETTY_PRINT)))
                ->dehydrateStateUsing(function ($state) {
                    $decoded = json_decode((string) $state, true);
                    if (!is_array($decoded)) {
                        throw \Illuminate\Validation\ValidationException::withMessages(['schema' => 'The template configuration must be valid JSON.']);
                    }
                    return $decoded;
                })->helperText('Sections, page, subjects, summary, footer and styles are validated.'),
            Forms\Components\Toggle::make('active')->default(true),
        ]);
    }
    public static function table(Table $table): Table
    {
        return $table->columns([Tables\Columns\TextColumn::make('name')->searchable(), Tables\Columns\TextColumn::make('version'), Tables\Columns\IconColumn::make('active')->boolean(), Tables\Columns\TextColumn::make('updated_at')->dateTime()])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
    public static function getPages(): array { return ['index' => Pages\ListReportCardTemplates::route('/'), 'create' => Pages\CreateReportCardTemplate::route('/create'), 'edit' => Pages\EditReportCardTemplate::route('/{record}/edit')]; }
}
