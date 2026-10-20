<?php
namespace App\Models;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class AttendanceSession extends Model {
    use HasFactory, BelongsToSchool, Auditable;
    protected $fillable = ['school_id','academic_year_id','class_room_id','teacher_id','session_date','started_at','validated_at','status','sync_id'];
    protected function casts(): array { return ['session_date'=>'date','started_at'=>'datetime','validated_at'=>'datetime']; }
    protected static function booted(): void { static::creating(fn (self $m) => $m->sync_id ??= (string) \Illuminate\Support\Str::uuid()); }
    public function classRoom(): BelongsTo { return $this->belongsTo(ClassRoom::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_id'); }
    public function records(): HasMany { return $this->hasMany(AttendanceRecord::class); }
}
