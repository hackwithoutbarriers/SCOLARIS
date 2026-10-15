<?php

namespace App\Services;

use App\Models\AbsenceFollowUp;
use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AbsenceFollowUpService
{
    public function createForRecord(AttendanceRecord $record): AbsenceFollowUp
    {
        return AbsenceFollowUp::query()->firstOrCreate(
            ['attendance_record_id' => $record->id],
            ['school_id' => $record->school_id, 'status' => 'TO_CONTACT'],
        );
    }

    public function update(AttendanceRecord $record, User $user, string $status, ?string $channel, ?string $notes): AbsenceFollowUp
    {
        abort_unless(
            ($user->isAttendanceManager() || $user->isSecretary())
                && (int) $user->school_id === (int) $record->school_id,
            403,
        );
        if (! array_key_exists($status, AbsenceFollowUp::STATUSES)) {
            throw ValidationException::withMessages(['status' => 'Statut de suivi invalide.']);
        }
        if ($status === 'JUSTIFIED' && mb_strlen(trim((string) $notes)) < 5) {
            throw ValidationException::withMessages(['notes' => 'Une justification d’au moins 5 caractères est obligatoire.']);
        }

        $followUp = $this->createForRecord($record);
        $followUp->update([
            'status' => $status,
            'channel' => $channel ?: $followUp->channel,
            'contacted_by' => $status === 'TO_CONTACT' ? $followUp->contacted_by : $user->id,
            'contacted_at' => $status === 'TO_CONTACT' ? $followUp->contacted_at : ($followUp->contacted_at ?: now()),
            'justified_at' => $status === 'JUSTIFIED' ? now() : $followUp->justified_at,
            'notes' => $notes ?: $followUp->notes,
        ]);

        return $followUp->fresh();
    }
}
