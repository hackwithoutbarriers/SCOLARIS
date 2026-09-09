<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportCardResource\Pages;
use App\Models\ReportCard;
use App\Services\ReportCardRenderer;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReportCardResource extends Resource
{
    protected static ?string $model = ReportCard::class;
    protected static ?string $navigationGroup = 'Academic';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('student.full_name')->searchable(),
            Tables\Columns\TextColumn::make('academicYear.name'),
            Tables\Columns\TextColumn::make('term.name')->placeholder('Annual'),
            Tables\Columns\TextColumn::make('version'),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('generated_at')->dateTime(),
        ])->actions([
            Tables\Actions\Action::make('html')->url(fn (ReportCard $record) => route('report-cards.html', $record))->openUrlInNewTab(),
            Tables\Actions\Action::make('pdf')->url(fn (ReportCard $record) => route('report-cards.pdf', $record))->openUrlInNewTab(),
        ]);
    }
    public static function getPages(): array { return ['index' => Pages\ListReportCards::route('/')]; }
}
