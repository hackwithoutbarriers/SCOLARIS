<?php
namespace App\Filament\Resources\GradeResource\Pages;
use App\Filament\Resources\GradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListGrades extends ListRecords { protected static string $resource = GradeResource::class; protected function getHeaderActions(): array { return [Actions\Action::make('entry')->label('Saisie rapide')->url(fn () => \App\Filament\Pages\GradeEntry::getUrl()), Actions\Action::make('import')->label('Importer CSV')->url(fn () => \App\Filament\Pages\GradeImport::getUrl()), Actions\CreateAction::make()]; } }
