<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsurePasswordChange;
use App\Http\Middleware\SetApplicationLocale;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));
        $middleware->redirectGuestsTo(fn () => '/admin/login');
        $middleware->alias([
            'active' => EnsureActiveUser::class,
            'password.change' => EnsurePasswordChange::class,
        ]);
        $middleware->web(append: [SetApplicationLocale::class]);
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function ($request, $input) {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
