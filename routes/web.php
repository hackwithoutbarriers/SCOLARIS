<?php

use App\Http\Controllers\AcademicController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RegistrationRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => config('app.name'),
    'version' => trim((string) file_get_contents(base_path('VERSION'))),
]));

Route::view('/', 'welcome');
Route::redirect('/login', '/admin/login', 302);
Route::get('/language/{locale}', [\App\Http\Controllers\LocaleController::class, 'update'])->name('locale.update');
Route::get('/register', [RegistrationRequestController::class, 'create'])->name('registration-requests.create');
Route::post('/register', [RegistrationRequestController::class, 'store'])->middleware('throttle:5,60')->name('registration-requests.store');

Route::middleware('auth')->get('/teacher/attendance', fn () => view('teacher.attendance'));

Route::middleware(['auth', 'active', 'throttle:120,1'])->prefix('api')->group(function () {
    Route::get('teacher/classes', [AttendanceController::class, 'classes']);
    Route::get('teacher/classes/{classRoom}/students', [AttendanceController::class, 'students']);
    Route::get('attendance/sessions', [AttendanceController::class, 'index']);
    Route::post('attendance/sessions', [AttendanceController::class, 'store']);
    Route::get('attendance/sessions/{attendanceSession}', [AttendanceController::class, 'show']);
    Route::put('attendance/sessions/{attendanceSession}', [AttendanceController::class, 'sync']);
    Route::post('attendance/sessions/{attendanceSession}/sync', [AttendanceController::class, 'sync']);
    Route::post('attendance/sessions/{attendanceSession}/validate', [AttendanceController::class, 'validateSession']);
    Route::post('attendance/sync', [AttendanceController::class, 'syncEndpoint']);
    Route::get('attendance/history', [AttendanceController::class, 'history']);
    Route::get('attendance/dashboard', [AttendanceController::class, 'dashboard']);
    Route::get('academic/students/{student}/grades/{term?}', [AcademicController::class, 'grades']);
    Route::post('academic/assessments/{assessment}/grades', [AcademicController::class, 'storeGrade']);
    Route::post('academic/grades/import', [AcademicController::class, 'importGrades']);
    Route::post('payments', [PaymentController::class, 'store']);
    Route::get('payments/debtors', [PaymentController::class, 'debtors']);
    Route::get('payments/report', [PaymentController::class, 'report']);
});
Route::post('api/payments/webhooks/{provider}', [PaymentController::class, 'webhook'])
    ->middleware('throttle:120,1');

Route::middleware('auth')->get('/report-cards/{reportCard}/html', [AcademicController::class, 'reportCardHtml'])->name('report-cards.html');
Route::middleware('auth')->get('/report-cards/{reportCard}/pdf', [AcademicController::class, 'reportCardPdf'])->name('report-cards.pdf');
Route::middleware('auth')->get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
