<?php

namespace App\Filament\Resources\SchoolSubscriptionResource\Pages;

use App\Filament\Resources\SchoolSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchoolSubscriptions extends ListRecords
{
    protected static string $resource = SchoolSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Créer un abonnement')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
