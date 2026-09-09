<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class CollectionReminder extends Model
{
    use BelongsToSchool, Auditable;
    protected $fillable = ['school_id', 'student_id', 'invoice_id', 'type', 'scheduled_period', 'channel', 'status', 'provider_response'];
    protected function casts(): array { return ['scheduled_period' => 'date', 'provider_response' => 'array']; }
}
