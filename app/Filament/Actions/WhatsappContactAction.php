<?php

namespace App\Filament\Actions;

use App\Http\Controllers\NotificationController;
use Filament\Tables\Actions\Action;

class WhatsappContactAction
{
    public static function make(
        callable $guardianResolver,
        string $templateCode,
        callable $variablesResolver,
        callable $contextResolver,
    ): Action {
        return Action::make('whatsapp_contact')
            ->label('Contacter sur WhatsApp')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->color('success')
            ->tooltip(fn ($record): string => ($guardianResolver($record)?->phone
                ? "Contacter {$guardianResolver($record)->full_name} — {$guardianResolver($record)->phone_display}"
                : 'Numéro WhatsApp absent ou invalide'))
            ->disabled(fn ($record): bool => ! filled($guardianResolver($record)?->phone))
            ->url(function ($record) use ($guardianResolver, $templateCode, $variablesResolver, $contextResolver): string {
                $guardian = $guardianResolver($record);
                if (! $guardian) {
                    return '#';
                }

                return NotificationController::whatsappUrl($guardian, $templateCode, $variablesResolver($record), $contextResolver($record));
            })
            ->openUrlInNewTab();
    }
}
