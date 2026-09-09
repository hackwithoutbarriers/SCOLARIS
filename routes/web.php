<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => config('app.name'),
    'version' => trim((string) file_get_contents(base_path('VERSION'))),
]));

Route::redirect('/login', '/admin/login', 302);
Route::get('/register', [\App\Http\Controllers\RegistrationRequestController::class, 'create'])->name('registration-requests.create');
Route::post('/register', [\App\Http\Controllers\RegistrationRequestController::class, 'store'])->middleware('throttle:5,60')->name('registration-requests.store');

Route::view('/', 'welcome');
Route::middleware('auth')->get('/teacher/attendance', fn () => view('teacher.attendance'));

Route::middleware(['auth', 'throttle:120,1'])->prefix('api')->group(function () {
    Route::get('teacher/classes', [\App\Http\Controllers\AttendanceController::class, 'classes']);
    Route::get('teacher/classes/{classRoom}/students', [\App\Http\Controllers\AttendanceController::class, 'students']);
    Route::get('attendance/sessions', [\App\Http\Controllers\AttendanceController::class, 'index']);
    Route::post('attendance/sessions', [\App\Http\Controllers\AttendanceController::class, 'store']);
    Route::get('attendance/sessions/{attendanceSession}', [\App\Http\Controllers\AttendanceController::class, 'show']);
    Route::put('attendance/sessions/{attendanceSession}', [\App\Http\Controllers\AttendanceController::class, 'sync']);
    Route::post('attendance/sessions/{attendanceSession}/sync', [\App\Http\Controllers\AttendanceController::class, 'sync']);
    Route::post('attendance/sessions/{attendanceSession}/validate', [\App\Http\Controllers\AttendanceController::class, 'validateSession']);
    Route::post('attendance/sync', [\App\Http\Controllers\AttendanceController::class, 'syncEndpoint']);
    Route::get('attendance/history', [\App\Http\Controllers\AttendanceController::class, 'history']);
    Route::get('attendance/dashboard', [\App\Http\Controllers\AttendanceController::class, 'dashboard']);
    Route::get('academic/students/{student}/grades/{term?}', [\App\Http\Controllers\AcademicController::class, 'grades']);
    Route::post('academic/assessments/{assessment}/grades', [\App\Http\Controllers\AcademicController::class, 'storeGrade']);
    Route::post('academic/grades/import', [\App\Http\Controllers\AcademicController::class, 'importGrades']);
    Route::post('payments', [\App\Http\Controllers\PaymentController::class, 'store']);
    Route::get('payments/debtors', [\App\Http\Controllers\PaymentController::class, 'debtors']);
    Route::get('payments/report', [\App\Http\Controllers\PaymentController::class, 'report']);
    Route::post('payments/webhooks/{provider}', [\App\Http\Controllers\PaymentController::class, 'webhook'])->withoutMiddleware('auth');
});

Route::middleware('auth')->get('/report-cards/{reportCard}/html', [\App\Http\Controllers\AcademicController::class, 'reportCardHtml'])->name('report-cards.html');
Route::middleware('auth')->get('/report-cards/{reportCard}/pdf', [\App\Http\Controllers\AcademicController::class, 'reportCardPdf'])->name('report-cards.pdf');
Route::middleware('auth')->get('/payments/{payment}/receipt', [\App\Http\Controllers\PaymentController::class, 'receipt'])->name('payments.receipt');
