<?php
namespace App\Filament\Resources\SubjectConfigResource\Pages;
use App\Filament\Resources\SubjectConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListSubjectConfigs extends ListRecords { protected static string $resource = SubjectConfigResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
