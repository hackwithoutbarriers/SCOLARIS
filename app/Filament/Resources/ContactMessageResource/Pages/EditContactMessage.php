<?php

namespace App\Filament\Resources\ContactMessageResource\Pages;

use App\Filament\Resources\ContactMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContactMessage extends EditRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->markAsRead();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
