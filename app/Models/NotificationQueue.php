<?php
namespace App\Models;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class NotificationQueue extends Model {
    use HasFactory, BelongsToSchool;
    protected $table = 'notification_queue';
    protected $fillable = ['school_id','student_id','guardian_id','channel','provider','recipient','template','event','payload','status','attempts','scheduled_at','sent_at','failed_at','provider_message_id','idempotency_key','error'];
    protected function casts(): array { return ['payload'=>'array','scheduled_at'=>'datetime','sent_at'=>'datetime','failed_at'=>'datetime']; }
}
