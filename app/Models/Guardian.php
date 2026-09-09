<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Guardian extends Model
{
    use HasFactory, BelongsToSchool, Auditable;
    protected $fillable = ['school_id', 'first_name', 'last_name', 'name', 'relationship', 'email', 'phone', 'secondary_phone', 'address', 'active'];
    protected function casts(): array { return ['active' => 'boolean']; }
    public function getFullNameAttribute(): string { return trim($this->name ?: implode(' ', array_filter([$this->first_name, $this->last_name]))); }
    public function students(): BelongsToMany { return $this->belongsToMany(Student::class, 'guardian_student')->withPivot('is_primary', 'receives_sms', 'receives_whatsapp')->withTimestamps(); }
}
