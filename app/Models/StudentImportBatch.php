<?php

namespace App\Models;

use App\Enums\StudentImportStatus;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentImportBatch extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'academic_year_id', 'class_room_id', 'created_by', 'file_name', 'file_sha256',
        'file_path', 'status', 'total_rows', 'processed_rows', 'created_count', 'updated_count',
        'ignored_count', 'failed_count', 'duplicate_count', 'warning_count', 'mapping_json', 'mapping_fingerprint',
        'delimiter', 'header_row',
        'started_at', 'completed_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'mapping_json' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function statusEnum(): StudentImportStatus
    {
        return StudentImportStatus::from($this->status);
    }

    public function transitionTo(StudentImportStatus $status): void
    {
        $this->statusEnum()->assertCanTransitionTo($status);
        $this->status = $status->value;
        $this->save();
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(StudentImportRow::class);
    }
}
