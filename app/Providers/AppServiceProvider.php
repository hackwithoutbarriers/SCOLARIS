<?php

namespace App\Providers;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TeacherAssignment;
use App\Models\SubjectConfig;
use App\Models\Assessment;
use App\Models\Grade;
use App\Models\Appreciation;
use App\Models\ReportCardTemplate;
use App\Models\ReportCard;
use App\Models\ReportCardVersion;
use App\Models\EvaluationRuleSet;
use App\Policies\SchoolResourcePolicy;
use App\Support\Tenancy\SchoolContext;
use App\Services\{HttpNotificationProvider, MockNotificationProvider, NotificationProvider};
use App\Services\Payments\{FakePaymentGateway, ManualPaymentGateway, PaymentGatewayInterface};
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
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
            return config('attendance.notifications.provider') === 'http'
                ? app(HttpNotificationProvider::class)
                : app(MockNotificationProvider::class);
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
            $connection->setSchemaGrammar(new \App\Database\Schema\Grammars\NeonPostgresGrammar($connection));
        }

        Gate::before(fn ($user) => $user->isSuperAdmin() ? true : null);
        Gate::policy(\App\Models\Payment::class, \App\Policies\PaymentPolicy::class);
        foreach ([AcademicYear::class, ClassRoom::class, Enrollment::class, Guardian::class, Student::class, Subject::class, Term::class, TeacherAssignment::class, SubjectConfig::class, EvaluationRuleSet::class, Assessment::class, Grade::class, Appreciation::class, ReportCardTemplate::class, ReportCard::class, ReportCardVersion::class, \App\Models\AttendanceSession::class, \App\Models\AttendanceRecord::class] as $model) {
            Gate::policy($model, SchoolResourcePolicy::class);
        }
        Gate::policy(\App\Models\Student::class, \App\Policies\AcademicPolicy::class);
        Gate::policy(\App\Models\Assessment::class, \App\Policies\AcademicPolicy::class);
        Gate::policy(\App\Models\ReportCard::class, \App\Policies\AcademicPolicy::class);
    }
}
