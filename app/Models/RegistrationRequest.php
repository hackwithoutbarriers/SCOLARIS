<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationRequest extends Model
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $fillable = [
        'school_id', 'school_name', 'school_code', 'name', 'email', 'phone',
        'requested_role', 'password_hash', 'status', 'reviewed_by',
        'reviewed_at', 'rejection_reason',
    ];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
