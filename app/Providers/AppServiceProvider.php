<?php

namespace App\Providers;

use App\Database\Schema\Grammars\NeonPostgresGrammar;
use App\Models\AcademicYear;
use App\Models\Appreciation;
use App\Models\Assessment;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Certificate;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\EvaluationRuleSet;
use App\Models\Expense;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\ReportCard;
use App\Models\ReportCardTemplate;
use App\Models\ReportCardVersion;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentImportBatch;
use App\Models\StudentImportRow;
use App\Models\Subject;
use App\Models\SubjectConfig;
use App\Models\TeacherAssignment;
use App\Models\Term;
use App\Models\TimetableSlot;
use App\Policies\AcademicPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\GradePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\SchoolPolicy;
use App\Policies\SchoolResourcePolicy;
use App\Policies\StudentImportBatchPolicy;
use App\Policies\StudentImportRowPolicy;
use App\Policies\SecretaryResourcePolicy;
use App\Policies\TimetableSlotPolicy;
use App\Services\EmailNotificationProvider;
use App\Services\HttpNotificationProvider;
use App\Services\MockNotificationProvider;
use App\Services\NotificationProvider;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\ManualPaymentGateway;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\WhatsAppNotificationProvider;
use App\Support\Tenancy\SchoolContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SchoolContext::class);
        $this->app->bind(NotificationProvider::class, function () {
            if (config('attendance.notifications.default_channel') === 'email') {
                return app(EmailNotificationProvider::class);
            }

            if (config('attendance.notifications.provider') === 'http') {
                return app(HttpNotificationProvider::class);
            }

            if (config('attendance.notifications.provider') === 'twilio'
                && config('attendance.notifications.default_channel') === 'whatsapp'
                && filled(config('services.twilio.sid'))) {
                return app(WhatsAppNotificationProvider::class);
            }

            return app(MockNotificationProvider::class);
        });
        $this->app->bind(PaymentGatewayInterface::class, function () {
            return config('payments.gateway', 'manual') === 'fake'
                ? app(FakePaymentGateway::class)
                : app(ManualPaymentGateway::class);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        if (config('database.default') === 'pgsql' && env('DB_NEON_POOLER', false)) {
            $connection = DB::connection('pgsql');
            $connection->setSchemaGrammar(new NeonPostgresGrammar($connection));
        }

        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Expense::class, ExpensePolicy::class);
        Gate::policy(TimetableSlot::class, TimetableSlotPolicy::class);
        Gate::policy(Certificate::class, SchoolResourcePolicy::class);
        Gate::policy(School::class, SchoolPolicy::class);
        Gate::policy(StudentImportBatch::class, StudentImportBatchPolicy::class);
        Gate::policy(StudentImportRow::class, StudentImportRowPolicy::class);
        foreach ([AcademicYear::class, ClassRoom::class, Enrollment::class, Guardian::class, Student::class, Subject::class, Term::class, TeacherAssignment::class, SubjectConfig::class, EvaluationRuleSet::class, Assessment::class, Grade::class, Appreciation::class, ReportCardTemplate::class, ReportCard::class, ReportCardVersion::class, AttendanceSession::class, AttendanceRecord::class] as $model) {
            Gate::policy($model, SchoolResourcePolicy::class);
        }
        Gate::policy(Student::class, AcademicPolicy::class);
        Gate::policy(Assessment::class, AcademicPolicy::class);
        Gate::policy(ReportCard::class, AcademicPolicy::class);
        Gate::policy(Grade::class, GradePolicy::class);
        foreach ([Guardian::class, Enrollment::class] as $model) {
            Gate::policy($model, SecretaryResourcePolicy::class);
        }
    }
}
