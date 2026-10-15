<?php

namespace App\Filament\Resources\DevisResource\Pages;

use App\Filament\Resources\DevisResource;
use App\Models\Devis;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDevis extends EditRecord
{
    protected static string $resource = DevisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('telecharger_pdf')
                ->label('Télécharger PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (Devis $record): string => route('devis.pdf', ['devis' => $record]))
                ->openUrlInNewTab(),
            Actions\DeleteAction::make(),
        ];
    }
}
