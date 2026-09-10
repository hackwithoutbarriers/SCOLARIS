<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Certificate extends Model {
    use HasFactory, BelongsToSchool;
    protected $fillable = ['school_id','student_id','template_id','document_type','number','data','generated_by'];
    protected function casts(): array { return ['data'=>'array']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function template(): BelongsTo { return $this->belongsTo(ReportCardTemplate::class,'template_id'); }
    public function generatedBy(): BelongsTo { return $this->belongsTo(User::class,'generated_by'); }
}
