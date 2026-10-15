<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashClosure extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'closure_date', 'theoretical_balance', 'physical_balance',
        'variance', 'status', 'counted_by', 'approved_by', 'approved_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['closure_date' => 'date', 'approved_at' => 'datetime', 'theoretical_balance' => 'integer', 'physical_balance' => 'integer', 'variance' => 'integer'];
    }

    public function counter(): BelongsTo { return $this->belongsTo(User::class, 'counted_by'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
}
