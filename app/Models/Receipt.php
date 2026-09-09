<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'payment_id', 'number', 'balance_after'];
    protected function casts(): array { return ['balance_after' => 'integer']; }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
}
