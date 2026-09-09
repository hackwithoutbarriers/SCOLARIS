<?php
namespace App\Models;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AttendanceRecord extends Model {
    use HasFactory, BelongsToSchool, Auditable;
    protected $fillable = ['school_id','attendance_session_id','student_id','status','late_minutes','marked_at','marked_by','comment','device_id','client_operation_id','synced_at'];
    protected function casts(): array { return ['marked_at'=>'datetime','synced_at'=>'datetime']; }
    public function session(): BelongsTo { return $this->belongsTo(AttendanceSession::class, 'attendance_session_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function marker(): BelongsTo { return $this->belongsTo(User::class, 'marked_by'); }
}
