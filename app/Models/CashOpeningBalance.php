<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashOpeningBalance extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'balance_date', 'amount', 'reason', 'recorded_by'];
    protected function casts(): array { return ['balance_date' => 'date', 'amount' => 'integer']; }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
