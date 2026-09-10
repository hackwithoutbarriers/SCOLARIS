<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class ReportCardVersion extends Model
{
    use HasFactory, BelongsToSchool, Auditable;
    protected $fillable = ['school_id', 'report_card_id', 'version', 'status', 'data', 'created_by'];
    protected function casts(): array { return ['data' => 'array']; }
    protected static function booted(): void
    {
        static::saving(function (self $version): void {
            if ($version->exists && $version->isDirty()) {
                throw ValidationException::withMessages(['version' => 'Les instantanés de bulletin sont immuables.']);
            }
        });
        static::deleting(function (): void {
            throw ValidationException::withMessages(['version' => 'Les instantanés de bulletin sont immuables.']);
        });
    }
    public function reportCard(): BelongsTo { return $this->belongsTo(ReportCard::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
