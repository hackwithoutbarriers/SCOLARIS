<?php

namespace App\Console\Commands;

use App\Enums\StudentImportStatus;
use App\Models\AuditLog;
use App\Models\StudentImportBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class PurgeStudentImportsCommand extends Command
{
    protected $signature = 'student-imports:purge';

    protected $description = 'Purge les fichiers et données de staging d’imports arrivés à expiration';

    public function handle(): int
    {
        $batches = StudentImportBatch::withoutGlobalScopes()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('status', '!=', StudentImportStatus::Processing->value)
            ->orderBy('id')
            ->pluck('id');

        foreach ($batches as $batchId) {
            DB::transaction(function () use ($batchId): void {
                $batch = StudentImportBatch::withoutGlobalScopes()->lockForUpdate()->findOrFail($batchId);
                if (! $batch->expires_at || $batch->expires_at->isFuture()
                    || $batch->status === StudentImportStatus::Processing->value) {
                    return;
                }

                $disk = Storage::disk(config('student-import.disk', 'local'));
                if ($batch->file_path && $disk->exists($batch->file_path)
                    && ! $disk->delete($batch->file_path)) {
                    throw new RuntimeException('Le fichier privé expiré n’a pas pu être supprimé.');
                }

                $batch->rows()->delete();
                $batch->file_path = null;
                $batch->expires_at = null;
                if (! in_array($batch->status, [StudentImportStatus::Completed->value, StudentImportStatus::Cancelled->value], true)) {
                    $batch->transitionTo(StudentImportStatus::Cancelled);
                } else {
                    $batch->save();
                }

                AuditLog::create([
                    'school_id' => $batch->school_id,
                    'user_id' => null,
                    'action' => 'student_import.purged',
                    'auditable_type' => StudentImportBatch::class,
                    'auditable_id' => $batch->id,
                    'old_values' => null,
                    'new_values' => ['file_sha256' => $batch->file_sha256, 'purged_at' => now()->toIso8601String()],
                ]);
            }, 3);
        }

        $this->info(count($batches).' lot(s) purgé(s).');

        return self::SUCCESS;
    }
}
