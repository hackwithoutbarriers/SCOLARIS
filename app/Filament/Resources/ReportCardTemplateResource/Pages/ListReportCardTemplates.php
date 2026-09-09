<?php
namespace App\Filament\Resources\ReportCardTemplateResource\Pages;
use App\Filament\Resources\ReportCardTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListReportCardTemplates extends ListRecords { protected static string $resource = ReportCardTemplateResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
