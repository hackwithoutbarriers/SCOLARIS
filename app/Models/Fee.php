<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fee extends Model
{
    use BelongsToSchool, Auditable;

    protected $fillable = ['school_id', 'fee_structure_id', 'name', 'description', 'amount', 'currency', 'mandatory', 'type', 'due_date', 'sort_order', 'active'];
    protected function casts(): array { return ['amount' => 'integer', 'mandatory' => 'boolean', 'active' => 'boolean', 'due_date' => 'date']; }
    public function feeStructure(): BelongsTo { return $this->belongsTo(FeeStructure::class); }
}
