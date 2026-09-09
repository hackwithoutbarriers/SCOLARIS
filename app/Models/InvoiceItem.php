<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use BelongsToSchool;
    protected $fillable = ['school_id', 'invoice_id', 'fee_id', 'description', 'amount'];
    protected function casts(): array { return ['amount' => 'integer']; }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function fee(): BelongsTo { return $this->belongsTo(Fee::class); }
}
