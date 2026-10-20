<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\School;

final class SuperAdminInterventionService
{
    public function record(School $school, string $action, array $context = []): AuditLog
    {
        $user = auth()->user();
        abort_unless($user?->isSuperAdmin(), 403);

        return AuditLog::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'action' => 'super_admin.'.$action,
            'auditable_type' => School::class,
            'auditable_id' => $school->id,
            'old_values' => [],
            'new_values' => $context,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
