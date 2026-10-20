<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_id', 'guardian_id', 'channel', 'template_code',
        'sent_by', 'status', 'payload',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function guardian(): BelongsTo { return $this->belongsTo(Guardian::class); }
    public function sender(): BelongsTo { return $this->belongsTo(User::class, 'sent_by'); }
}
