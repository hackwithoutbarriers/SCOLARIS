<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReversal extends Model
{
    use BelongsToSchool, Auditable;
    protected $fillable = ['school_id', 'payment_id', 'invoice_id', 'amount', 'reason', 'created_by'];
    protected function casts(): array { return ['amount' => 'integer']; }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
