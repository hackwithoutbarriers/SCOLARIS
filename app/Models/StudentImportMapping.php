<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class StudentImportMapping extends Model
{
    use BelongsToSchool;

    protected $fillable = ['school_id', 'created_by', 'fingerprint', 'mapping_json'];

    protected function casts(): array
    {
        return ['mapping_json' => 'array'];
    }
}
