<?php

namespace App\Filament\Resources\GradeResource\Pages;

use App\Filament\Pages\GradeEntry;
use App\Filament\Pages\GradeImport;
use App\Filament\Resources\GradeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGrades extends ListRecords
{
    protected static string $resource = GradeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\Action::make('entry')->label('Saisie rapide')->url(fn () => GradeEntry::getUrl()), Actions\Action::make('import')->label('Importer CSV')->url(fn () => GradeImport::getUrl()), Actions\CreateAction::make()];
    }
}
