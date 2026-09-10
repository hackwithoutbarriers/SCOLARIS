<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseReversal extends Model
{
    use Auditable, BelongsToSchool;

    protected $fillable = ['school_id', 'expense_id', 'amount', 'reason', 'created_by'];
    protected function casts(): array { return ['amount' => 'integer']; }
    public function expense(): BelongsTo { return $this->belongsTo(Expense::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
