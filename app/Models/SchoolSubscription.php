<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSubscription extends Model
{
    use Auditable, BelongsToSchool;

    protected $fillable = ['school_id', 'subscription_plan_id', 'status', 'starts_at', 'ends_at', 'trial_ends_at', 'cancelled_at', 'notes'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'trial_ends_at' => 'date', 'cancelled_at' => 'datetime'];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['trial', 'active'], true)
            && $this->starts_at?->isPast()
            && (! $this->ends_at || $this->ends_at->isFuture());
    }
}
