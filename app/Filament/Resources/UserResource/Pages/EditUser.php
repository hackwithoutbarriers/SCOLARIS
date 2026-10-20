<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\AuditLog;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        abort_unless(
            auth()->user()?->isSuperAdmin()
                && auth()->user()->hasActiveSchoolIntervention((int) $record->school_id),
            403,
        );

        unset($data['school_id']);

        return DB::transaction(function () use ($record, $data): Model {
            $fields = ['role', 'secondary_role', 'is_active'];
            $oldAccess = collect($fields)->mapWithKeys(fn (string $field): array => [$field => $record->getAttribute($field)])->all();

            $record->update($data);
            $updatedRecord = $record->fresh();
            $newAccess = collect($fields)->mapWithKeys(fn (string $field): array => [$field => $updatedRecord->getAttribute($field)])->all();
            $changedFields = array_keys(array_filter(
                $newAccess,
                fn (mixed $value, string $field): bool => $value !== $oldAccess[$field],
                ARRAY_FILTER_USE_BOTH,
            ));

            if ($changedFields !== []) {
                AuditLog::create([
                    'school_id' => $record->school_id,
                    'user_id' => auth()->id(),
                    'action' => 'user.access_updated',
                    'auditable_type' => $record::class,
                    'auditable_id' => $record->getKey(),
                    'old_values' => array_intersect_key($oldAccess, array_flip($changedFields)),
                    'new_values' => array_intersect_key($newAccess, array_flip($changedFields)),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]);
            }

            return $updatedRecord;
        });
    }
}
