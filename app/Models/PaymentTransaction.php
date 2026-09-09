<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'payment_id', 'provider', 'provider_transaction_id', 'merchant_reference', 'status', 'attempts', 'request_payload', 'response_payload', 'last_error'];
    protected function casts(): array { return ['request_payload' => 'array', 'response_payload' => 'array']; }
    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
}
