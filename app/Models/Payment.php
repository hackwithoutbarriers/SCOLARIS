<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use BelongsToSchool, Auditable;
    public const PENDING = 'PENDING';
    public const CONFIRMED = 'CONFIRMED';
    public const FAILED = 'FAILED';
    public const CANCELLED = 'CANCELLED';
    public const REFUNDED = 'REFUNDED';
    protected $fillable = ['school_id', 'student_id', 'amount', 'currency', 'payment_method', 'status', 'reference', 'idempotency_key', 'paid_at', 'received_by', 'notes', 'unallocated_amount'];
    protected function casts(): array { return ['amount' => 'integer', 'unallocated_amount' => 'integer', 'paid_at' => 'datetime']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function allocations(): HasMany { return $this->hasMany(PaymentAllocation::class); }
    public function transaction(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(PaymentTransaction::class); }
    public function receipt(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(Receipt::class); }
    public function reversals(): HasMany { return $this->hasMany(PaymentReversal::class); }
}
