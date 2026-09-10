<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConductLabel extends Model
{
    use Auditable, BelongsToSchool, HasFactory;

    protected $fillable = ['school_id', 'label', 'sort_order', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public static function defaults(): array
    {
        return ['Bonne conduite', 'Conduite à surveiller', 'Avertissement conduite', 'Blâme'];
    }
}
