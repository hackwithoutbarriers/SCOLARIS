<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\ClassRoom;
use App\Models\Enrollment;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function canManage(AttendanceSession $session, ?User $user): bool
    {
        return $user && $user->school_id === $session->school_id &&
            ($user->isAdmin() || $session->teacher_id === $user->id ||
            TeacherAssignment::where('teacher_id', $user->id)
                ->where('class_room_id', $session->class_room_id)
                ->where('academic_year_id', $session->academic_year_id)
                ->exists());
    }

    public function create(array $data, User $user): AttendanceSession
    {
        $data['school_id'] = $user->school_id;
        $data['teacher_id'] = $user->isAdmin() && isset($data['teacher_id']) ? (int) $data['teacher_id'] : $user->id;
        if (! User::where('id', $data['teacher_id'])->where('school_id', $user->school_id)->exists()) {
            abort(403);
        }
        $class = ClassRoom::withoutGlobalScopes()->findOrFail($data['class_room_id']);
        abort_unless((int) $class->school_id === (int) $user->school_id, 403);
        $data['academic_year_id'] = $class->academic_year_id;
        if (! $user->isAdmin() && ! TeacherAssignment::where('teacher_id', $user->id)
            ->where('class_room_id', $class->id)->where('academic_year_id', $class->academic_year_id)->exists()) {
            abort(403);
        }
        if (! empty($data['sync_id'])) {
            return AttendanceSession::updateOrCreate(
                ['school_id' => $data['school_id'], 'sync_id' => $data['sync_id']],
                $data
            );
        }

        return AttendanceSession::create(array_merge($data, [
            'status' => 'OPEN',
            'started_at' => $data['started_at'] ?? now(),
        ]));
    }

    public function sync(AttendanceSession $session, array $records): array
    {
        return DB::transaction(function () use ($session, $records): array {
            $session = AttendanceSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $result = [];
            if ($session->status === 'VALIDATED') {
                throw ValidationException::withMessages(['session' => 'Une séance validée nécessite une correction contrôlée.']);
            }

            foreach ($records as $data) {
                $validated = validator($data, [
                    'student_id' => 'required|integer',
                    'status' => 'required|in:PRESENT,ABSENT,LATE,EXCUSED,present,absent,late,excused',
                    'late_minutes' => 'nullable|integer|min:0|max:1440',
                    'marked_at' => 'nullable|date',
                    'comment' => 'nullable|string|max:1000',
                    'device_id' => 'nullable|string|max:191',
                    'client_operation_id' => 'nullable|uuid',
                ])->validate();
                $validated['status'] = strtoupper($validated['status']);
                $student = Enrollment::where('school_id', $session->school_id)
                    ->where('academic_year_id', $session->academic_year_id)
                    ->where('class_room_id', $session->class_room_id)
                    ->where('student_id', $validated['student_id'])
                    ->where('status', 'active')->exists();
                if (! $student) {
                    throw ValidationException::withMessages(['student_id' => 'Cet élève n’est pas inscrit dans cette classe.']);
                }
                $key = ['attendance_session_id' => $session->id, 'student_id' => $validated['student_id']];
                if (! empty($validated['client_operation_id'])) {
                    $duplicate = AttendanceRecord::where('school_id', $session->school_id)
                        ->where('client_operation_id', $validated['client_operation_id'])->first();
                    if ($duplicate) {
                        $result[] = $duplicate->fresh();

                        continue;
                    }

                }
                $record = AttendanceRecord::updateOrCreate($key, array_merge($validated, [
                    'school_id' => $session->school_id,
                    'marked_by' => auth()->id(),
                    'marked_at' => $validated['marked_at'] ?? now(),
                    'synced_at' => now(),
                    'client_operation_id' => $validated['client_operation_id'] ?? Str::uuid(),
                ]));
                $result[] = $record->fresh();
            }

            return $result;
        });
    }

    public function correct(AttendanceSession $session, array $records, User $user): array
    {
        abort_unless($this->canManage($session, $user) && $user->isAdmin(), 403);
        if ($session->status !== 'VALIDATED') {
            return $this->sync($session, $records);
        }

        return DB::transaction(function () use ($session, $records, $user): array {
            $session->load('records');
            $result = [];
            foreach ($records as $data) {
                $validated = validator($data, [
                    'student_id' => 'required|integer',
                    'status' => 'required|in:PRESENT,ABSENT,LATE,EXCUSED,present,absent,late,excused',
                    'late_minutes' => 'nullable|integer|min:0|max:1440',
                    'comment' => 'required|string|max:1000',
                ])->validate();
                $validated['status'] = strtoupper($validated['status']);
                $record = $session->records->firstWhere('student_id', $validated['student_id']);
                abort_unless($record, 422, 'Cet élève n’a aucun relevé de présence dans cette séance.');
                $record->update(array_merge($validated, [
                    'marked_by' => $user->id,
                    'marked_at' => now(),
                    'synced_at' => now(),
                ]));
                $result[] = $record->fresh();
            }
            app(NotificationService::class)->queueForValidatedSession($session->fresh());

            return $result;
        });
    }

    public function validateSession(AttendanceSession $session, User $user): AttendanceSession
    {
        abort_unless($this->canManage($session, $user), 403);
        if ($session->status === 'CANCELLED') {
            throw ValidationException::withMessages(['session' => 'Une séance annulée ne peut pas être validée.']);
        }

        return DB::transaction(function () use ($session): AttendanceSession {
            $locked = AttendanceSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'VALIDATED') {
                $locked->update(['status' => 'VALIDATED', 'validated_at' => now()]);
            }
            app(NotificationService::class)->queueForValidatedSession($locked);

            return $locked->fresh();
        });
    }
}
