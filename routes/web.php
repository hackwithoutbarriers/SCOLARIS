<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => config('app.name'),
        'version' => trim((string) file_get_contents(base_path('VERSION'))),
    ]);
});

Route::redirect('/', '/admin/login', 302);
Route::redirect('/login', '/admin/login', 302);

Route::middleware('auth')->get(
    '/teacher/attendance',
    fn () => view('teacher.attendance')
);

Route::middleware(['auth', 'throttle:120,1'])
    ->prefix('api')
    ->group(function () {
        // routes API existantes
    });

// routes report-cards / payments existantes
