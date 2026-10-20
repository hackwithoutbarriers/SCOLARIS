<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ReceiptSequence extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'sequence_type', 'next_number'];

    protected function casts(): array
    {
        return ['next_number' => 'integer'];
    }
}
