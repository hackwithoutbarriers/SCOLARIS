<?php

namespace App\Http\Middleware;

use App\Models\School;
use App\Services\SuperAdminInterventionService;
use App\Support\Tenancy\SchoolContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApplySchoolInterventionContext
{
    public function handle(Request $request, Closure $next): mixed
    {
        $context = app(SchoolContext::class);
        $user = $request->user();
        if (! $user?->isSuperAdmin()) {
            $context->clear();

            return $next($request);
        }

        $schoolId = (int) $request->session()->get('scolaris_intervention.school_id', 0);
        $expiresAt = (int) $request->session()->get('scolaris_intervention.expires_at', 0);

        if ($schoolId > 0 && $expiresAt <= now()->timestamp) {
            $expiredSchool = School::withoutGlobalScopes()->find($schoolId);
            $request->session()->forget('scolaris_intervention');
            $context->clear();
            if ($expiredSchool) {
                app(SuperAdminInterventionService::class)->record($expiredSchool, 'context_expired', [
                    'expires_at' => $expiresAt ?: null,
                ]);
            }
            $schoolId = 0;
        }

        if ($schoolId <= 0) {
            $context->clear();

            return $next($request);
        }

        $school = School::withoutGlobalScopes()->findOrFail($schoolId);
        $context->set($schoolId);
        try {
            if (! $request->attributes->get('scolaris_intervention_audited')) {
                $route = $request->route();
                $routeName = $route?->getName() ?: $route?->uri() ?: 'route inconnue';
                $operation = Str::contains(Str::lower($routeName), ['export', 'download', 'pdf', 'receipt', 'report', 'csv', 'excel', 'xlsx', 'xls', 'student-card'])
                    ? 'export'
                    : ($request->isMethodSafe() ? 'read' : 'write_or_action');
                app(SuperAdminInterventionService::class)->record($school, 'request_'.$operation, [
                    'route' => $routeName,
                    'method' => $request->method(),
                ]);
                $request->attributes->set('scolaris_intervention_audited', true);
            }

            return $next($request);
        } finally {
            $context->clear();
        }
    }
}
