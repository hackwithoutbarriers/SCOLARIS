<?php

namespace App\Filament\Concerns;

trait HasRoleNavigation
{
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isDirector() === true
            || ($user?->isSuperAdmin() === true && $user->hasActiveSchoolIntervention());
    }
}
