<?php
namespace App\Filament\Resources\ReportCardTemplateResource\Pages;
use App\Filament\Resources\ReportCardTemplateResource;
use App\Models\ReportCardTemplate;
use Filament\Resources\Pages\CreateRecord;
class CreateReportCardTemplate extends CreateRecord {
    protected static string $resource = ReportCardTemplateResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array { $data['schema'] = $data['schema_json'] ?? ReportCardTemplate::defaultSchema(); unset($data['schema_json']); return $data; }
}
