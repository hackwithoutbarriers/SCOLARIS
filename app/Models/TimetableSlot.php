<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;
class TimetableSlot extends Model {
    use HasFactory, BelongsToSchool;
    protected $fillable = ['school_id','academic_year_id','teacher_id','class_room_id','subject_id','day_of_week','starts_at','ends_at','room','notes'];
    protected static function booted(): void {
        static::saving(function (self $slot): void {
            if ($slot->day_of_week < 1 || $slot->day_of_week > 7) throw ValidationException::withMessages(['day_of_week'=>'Le jour doit être compris entre 1 et 7.']);
            if ($slot->starts_at >= $slot->ends_at) throw ValidationException::withMessages(['ends_at'=>'L’heure de fin doit être postérieure à l’heure de début.']);
            $overlap = self::query()->where('academic_year_id',$slot->academic_year_id)->where('teacher_id',$slot->teacher_id)->where('day_of_week',$slot->day_of_week)->when($slot->exists,fn($q)=>$q->where('id','<>',$slot->id))->where('starts_at','<',$slot->ends_at)->where('ends_at','>',$slot->starts_at)->exists();
            if ($overlap) throw ValidationException::withMessages(['teacher_id'=>'L’enseignant a déjà un cours sur ce créneau.']);
        });
    }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function teacher(): BelongsTo { return $this->belongsTo(User::class,'teacher_id'); }
    public function classRoom(): BelongsTo { return $this->belongsTo(ClassRoom::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
}
