<?php

namespace App\Services\Payments;

use App\Models\FeeStructure;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function generateForStudent(FeeStructure $structure, Student $student): Invoice
    {
        abort_unless($structure->school_id === $student->school_id, 403);
        $structure->load('fees');

        return DB::transaction(function () use ($structure, $student): Invoice {
            $existing = Invoice::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $structure->academic_year_id)
                ->where('fee_structure_id', $structure->id)
                ->first();

            if ($existing) {
                return $existing->load('items');
            }

            $fees = $structure->fees->where('active', true);
            abort_unless($fees->isNotEmpty(), 422, 'La grille tarifaire ne contient aucun frais actif.');
            $total = (int) $fees->sum('amount');
            $dueDate = $fees->filter(fn ($fee) => $fee->due_date !== null)->min('due_date') ?: today();
            $invoice = Invoice::create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'academic_year_id' => $structure->academic_year_id,
                'fee_structure_id' => $structure->id,
                'class_room_id' => $structure->class_room_id,
                'number' => 'INV-'.now()->format('Ym').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
                'total_amount' => $total,
                'due_date' => $dueDate,
                'status' => Invoice::ISSUED,
            ]);
            foreach ($fees as $fee) {
                InvoiceItem::create([
                    'school_id' => $student->school_id,
                    'invoice_id' => $invoice->id,
                    'fee_id' => $fee->id,
                    'description' => $fee->name,
                    'amount' => $fee->amount,
                ]);
            }

            return $invoice->load('items');
        });
    }
}
