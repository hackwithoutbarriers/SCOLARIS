<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expense extends Model
{
    use Auditable, BelongsToSchool;

    protected $fillable = ['school_id', 'category', 'amount', 'description', 'expense_date', 'recorded_by', 'voucher_number', 'justification_path', 'approval_status', 'approved_by', 'approved_at', 'approval_reason', 'rejected_by', 'rejected_at', 'correction_of_id'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'expense_date' => 'date', 'approved_at' => 'datetime', 'rejected_at' => 'datetime'];
    }

    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function reversals(): HasMany { return $this->hasMany(ExpenseReversal::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejector(): BelongsTo { return $this->belongsTo(User::class, 'rejected_by'); }
    public function correctedExpense(): BelongsTo { return $this->belongsTo(self::class, 'correction_of_id'); }
    public function netAmount(): int { return max(0, (int) $this->amount - (int) $this->reversals()->sum('amount')); }
}
