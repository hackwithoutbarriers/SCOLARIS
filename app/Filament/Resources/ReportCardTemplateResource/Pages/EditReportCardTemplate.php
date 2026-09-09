<?php
namespace App\Filament\Resources\ReportCardTemplateResource\Pages;
use App\Filament\Resources\ReportCardTemplateResource;
use Filament\Resources\Pages\EditRecord;
class EditReportCardTemplate extends EditRecord { protected static string $resource = ReportCardTemplateResource::class; protected function mutateFormDataBeforeSave(array $data): array { $data['schema'] = $data['schema_json'] ?? $this->record->schema; unset($data['schema_json']); return $data; } }
