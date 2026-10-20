<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use BelongsToSchool;
    public $timestamps = true;
    protected $fillable = ['school_id', 'user_id', 'action', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'ip_address', 'user_agent'];
    protected function casts(): array { return ['old_values' => 'array', 'new_values' => 'array']; }
}
