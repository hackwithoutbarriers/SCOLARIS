<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentImportRow extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id', 'student_import_batch_id', 'row_number', 'status', 'student_id',
        'candidate_student_id', 'decision', 'normalized_data', 'issues_json',
    ];

    protected function casts(): array
    {
        return [
            'normalized_data' => 'array',
            'issues_json' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(StudentImportBatch::class, 'student_import_batch_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function candidateStudent(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'candidate_student_id');
    }
}
