<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Certificate extends Model {
    use HasFactory, BelongsToSchool;
    protected $fillable = ['school_id','student_id','template_id','document_type','number','data','generated_by','status','requested_by','validated_by','validated_at','delivery_method','delivered_to','delivered_by','delivered_at','workflow_note','cancellation_reason'];
    protected function casts(): array { return ['data'=>'array','validated_at'=>'datetime','delivered_at'=>'datetime']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function template(): BelongsTo { return $this->belongsTo(ReportCardTemplate::class,'template_id'); }
    public function generatedBy(): BelongsTo { return $this->belongsTo(User::class,'generated_by'); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class,'requested_by'); }
    public function validator(): BelongsTo { return $this->belongsTo(User::class,'validated_by'); }
    public function deliverer(): BelongsTo { return $this->belongsTo(User::class,'delivered_by'); }
}
