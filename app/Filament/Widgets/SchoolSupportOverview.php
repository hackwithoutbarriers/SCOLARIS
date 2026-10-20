<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\School;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class SchoolSupportOverview extends Widget
{
    protected static string $view = 'filament.widgets.school-support-overview';

    protected static ?int $sort = -2;

    public static function canView(): bool
    {
        return auth()->user()?->isSuperAdmin() === true;
    }

    public function getSchools(): Collection
    {
        return School::query()
            ->withCount(['users', 'students', 'classRooms'])
            ->with('activeSubscription.plan')
            ->withSum(['invoices as overdue_amount' => fn ($query) => $query->where('status', Invoice::OVERDUE)], 'total_amount')
            ->orderByDesc('active')
            ->orderBy('name')
            ->get();
    }
}
