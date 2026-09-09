<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToSchool, Auditable;

    public const DRAFT = 'DRAFT';
    public const ISSUED = 'ISSUED';
    public const PARTIALLY_PAID = 'PARTIALLY_PAID';
    public const PAID = 'PAID';
    public const OVERDUE = 'OVERDUE';
    public const CANCELLED = 'CANCELLED';

    protected $fillable = ['school_id', 'student_id', 'academic_year_id', 'fee_structure_id', 'class_room_id', 'number', 'total_amount', 'currency', 'due_date', 'status', 'notes'];
    protected function casts(): array { return ['total_amount' => 'integer', 'due_date' => 'date']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function feeStructure(): BelongsTo { return $this->belongsTo(FeeStructure::class); }
    public function classRoom(): BelongsTo { return $this->belongsTo(ClassRoom::class); }
    public function items(): HasMany { return $this->hasMany(InvoiceItem::class); }
    public function allocations(): HasMany { return $this->hasMany(PaymentAllocation::class); }
    public function paidAmount(): int
    {
        if ($this->relationLoaded('allocations')) {
            $paid = (int) $this->allocations
                ->filter(fn (PaymentAllocation $allocation): bool => $allocation->payment?->status === Payment::CONFIRMED)
                ->sum('amount');
            $reversed = (int) $this->allocations
                ->flatMap(fn (PaymentAllocation $allocation) => ($allocation->payment?->reversals ?? collect())->where('invoice_id', $this->id))
                ->sum('amount');
            return max(0, $paid - $reversed);
        }
        $paid = (int) $this->allocations()->whereHas('payment', fn ($query) => $query->where('status', Payment::CONFIRMED))->sum('amount');
        $reversed = (int) PaymentReversal::query()->where('invoice_id', $this->id)
            ->whereHas('payment', fn ($query) => $query->where('status', Payment::CONFIRMED))->sum('amount');
        return max(0, $paid - $reversed);
    }
    public function balance(): int { return max(0, (int) $this->total_amount - $this->paidAmount()); }
    public function refreshStatus(): self
    {
        if ($this->status === self::CANCELLED || $this->status === self::DRAFT) return $this;
        $paid = $this->paidAmount();
        $this->status = $paid >= $this->total_amount ? self::PAID : ($this->due_date?->isBefore(today()) ? self::OVERDUE : ($paid > 0 ? self::PARTIALLY_PAID : self::ISSUED));
        $this->saveQuietly();
        return $this;
    }
}
