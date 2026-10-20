<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = ['name', 'code', 'monthly_amount', 'student_limit', 'features', 'active'];

    protected function casts(): array
    {
        return ['monthly_amount' => 'integer', 'student_limit' => 'integer', 'features' => 'array', 'active' => 'boolean'];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SchoolSubscription::class);
    }
}
