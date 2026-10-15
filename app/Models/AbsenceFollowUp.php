<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceFollowUp extends Model
{
    use BelongsToSchool;

    public const STATUSES = [
        'TO_CONTACT' => 'À contacter',
        'CONTACTED' => 'Contacté',
        'JUSTIFIED' => 'Justifiée',
        'UNJUSTIFIED' => 'Non justifiée',
        'TO_MONITOR' => 'À surveiller',
    ];

    protected $fillable = [
        'school_id', 'attendance_record_id', 'status', 'channel',
        'contacted_by', 'contacted_at', 'justified_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['contacted_at' => 'datetime', 'justified_at' => 'datetime'];
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class);
    }

    public function contactUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contacted_by');
    }
}
