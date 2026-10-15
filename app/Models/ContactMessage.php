<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ContactMessage extends Model
{
    protected $fillable = [
        'nom',
        'email',
        'telephone',
        'sujet',
        'message',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    public function isRead(): bool
    {
        return $this->read_at instanceof Carbon;
    }
}
