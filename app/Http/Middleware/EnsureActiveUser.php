<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->is_active !== true) {
            auth()->logout();
            abort(403, 'Compte désactivé.');
        }

        return $next($request);
    }
}
