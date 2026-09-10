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

    protected $fillable = ['school_id', 'category', 'amount', 'description', 'expense_date', 'recorded_by', 'voucher_number', 'justification_path'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'expense_date' => 'date'];
    }

    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function reversals(): HasMany { return $this->hasMany(ExpenseReversal::class); }
    public function netAmount(): int { return max(0, (int) $this->amount - (int) $this->reversals()->sum('amount')); }
}
