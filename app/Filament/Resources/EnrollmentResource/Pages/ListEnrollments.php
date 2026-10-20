<?php

namespace App\Filament\Resources\EnrollmentResource\Pages;

use App\Filament\Resources\EnrollmentResource;
use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEnrollments extends ListRecords
{
    protected static string $resource = EnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('registerStudent')
                ->label('Inscrire un élève')
                ->icon('heroicon-o-user-plus')
                ->url(StudentResource::getUrl('create')),
        ];
    }
}
