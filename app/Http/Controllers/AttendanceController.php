<?php
namespace App\Http\Controllers;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\TeacherAssignment;
use App\Models\Enrollment;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
class AttendanceController extends Controller {
    public function __construct(private AttendanceService $service) {}
    public function index(Request $request) {
        $user = $request->user();
        $query = AttendanceSession::withCount('records')->latest('session_date');
        if (!$user->isAdmin()) $query->where('teacher_id', $user->id);
        return $query->paginate($request->integer('per_page',20));
    }
    public function store(Request $request) {
        $data=$request->validate(['class_room_id'=>'required|integer','teacher_id'=>'nullable|integer','session_date'=>'required|date','sync_id'=>'nullable|uuid']);
        return response()->json($this->service->create($data,$request->user()),201);
    }
    public function show(Request $request, AttendanceSession $attendanceSession) {
        abort_unless($this->service->canManage($attendanceSession,$request->user()),403);
        return $attendanceSession->load('records.student');
    }
    public function sync(Request $request, AttendanceSession $attendanceSession) {
        abort_unless($this->service->canManage($attendanceSession,$request->user()),403);
        $data=$request->validate(['records'=>'required|array|min:1']);
        $records = $attendanceSession->status === 'VALIDATED'
            ? $this->service->correct($attendanceSession, $data['records'], $request->user())
            : $this->service->sync($attendanceSession, $data['records']);
        return response()->json(['data'=>$records]);
    }
    public function syncEndpoint(Request $request) {
        $data = $request->validate(['session_id' => 'required|integer', 'records' => 'required|array']);
        $session = AttendanceSession::findOrFail($data['session_id']);
        abort_unless($this->service->canManage($session, $request->user()), 403);
        return response()->json(['data' => $this->service->sync($session, $data['records'])]);
    }
    public function validateSession(Request $request, AttendanceSession $attendanceSession) {
        return response()->json($this->service->validateSession($attendanceSession, $request->user()));
    }
    public function classes(Request $request) {
        $user = $request->user();
        return $user->isAdmin()
            ? ClassRoom::with('academicYear')->get()
            : ClassRoom::whereHas('teacherAssignments', fn ($q) => $q->where('teacher_id', $user->id))->with('academicYear')->get();
    }
    public function students(Request $request, ClassRoom $classRoom) {
        abort_unless($request->user()->isAdmin() || TeacherAssignment::where('teacher_id', $request->user()->id)->where('class_room_id', $classRoom->id)->exists(), 403);
        return Enrollment::with('student')->where('class_room_id', $classRoom->id)->where('academic_year_id', $classRoom->academic_year_id)->where('status', 'active')->get()->pluck('student');
    }
    public function history(Request $request) {
        abort_unless($request->user()->isAdmin(), 403);
        $query = \App\Models\AttendanceRecord::with(['session.classRoom', 'session.teacher', 'student'])
            ->whereHas('session', fn ($q) => $q->whereBetween('session_date',[$request->input('from','1900-01-01'),$request->input('to','2999-12-31')]));
        if ($request->filled('class_room_id')) $query->whereHas('session', fn ($q) => $q->where('class_room_id', $request->integer('class_room_id')));
        if ($request->filled('teacher_id')) $query->whereHas('session', fn ($q) => $q->where('teacher_id', $request->integer('teacher_id')));
        return $query->latest()->paginate($request->integer('per_page',20));
    }
    public function dashboard(Request $request) {
        abort_unless($request->user()->isAdmin(), 403);
        $date = $request->input('date', now()->toDateString());
        $query = \App\Models\AttendanceRecord::whereHas('session', fn ($q) => $q->whereDate('session_date', $date));
        $total = (clone $query)->count();
        $classes = \App\Models\ClassRoom::whereDoesntHave('attendanceSessions', fn ($q) => $q->whereDate('session_date', $date))->count();
        return ['date'=>$date,'total'=>$total,'present'=>(clone $query)->where('status','PRESENT')->count(),'absent'=>(clone $query)->where('status','ABSENT')->count(),'late'=>(clone $query)->where('status','LATE')->count(),'excused'=>(clone $query)->where('status','EXCUSED')->count(),'classes_without_attendance'=>$classes];
    }
}
