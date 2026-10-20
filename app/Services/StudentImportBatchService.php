<?php

namespace App\Services;

use App\Enums\StudentImportStatus;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassRoom;
use App\Models\Guardian;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentImportBatch;
use App\Models\StudentImportMapping;
use App\Models\StudentImportRow;
use App\Models\User;
use App\Support\Tenancy\SchoolContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;

final class StudentImportBatchService
{
    public function __construct(
        private readonly StudentCsvAnalyzer $analyzer,
        private readonly StudentImportStorage $storage,
    ) {}

    public function create(UploadedFile $file, User $actor, int $academicYearId, int $classRoomId): StudentImportBatch
    {
        $schoolId = $this->schoolIdFor($actor);
        $size = $file->getSize();
        if ($size === false || $size === 0 || $size > config('student-import.max_file_size')) {
            throw new InvalidArgumentException('Le fichier est vide ou dépasse la taille maximale autorisée.');
        }

        if (strtolower($file->getClientOriginalExtension()) !== 'csv'
            || ! in_array($file->getMimeType(), ['text/csv', 'text/plain', 'application/csv', 'application/octet-stream'], true)) {
            throw new InvalidArgumentException('Choisissez un fichier CSV UTF-8.');
        }

        $sourcePath = $file->getRealPath();
        if (! $sourcePath || str_starts_with((string) file_get_contents($sourcePath, false, null, 0, 4), "PK\x03\x04")) {
            throw new InvalidArgumentException('Le contenu du fichier ne correspond pas à un CSV.');
        }
        $hash = hash_file('sha256', $sourcePath);
        if ($hash === false) {
            throw new RuntimeException('Impossible de calculer l’empreinte du fichier.');
        }

        $year = AcademicYear::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereKey($academicYearId)
            ->first();
        $classRoom = ClassRoom::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('academic_year_id', $academicYearId)
            ->whereKey($classRoomId)
            ->first();

        if (! $year || ! $year->isActive() || ! $classRoom) {
            throw ValidationException::withMessages(['academic_year_id' => 'Choisissez une année active et une classe de cette école.']);
        }

        $existing = StudentImportBatch::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('file_sha256', $hash)
            ->where('academic_year_id', $academicYearId)
            ->where('class_room_id', $classRoomId)
            ->where('status', '!=', StudentImportStatus::Cancelled->value)
            ->latest('id')
            ->first();
        if ($existing) {
            return $existing;
        }

        $rateLimitKey = "student-import:upload:{$schoolId}:{$actor->id}";
        $maxUploads = (int) config('student-import.uploads_per_hour', 10);
        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxUploads)) {
            throw ValidationException::withMessages(['file' => 'La limite horaire d’imports est atteinte. Réessayez plus tard.']);
        }
        RateLimiter::hit($rateLimitKey, 60 * 60);

        $activeStatuses = [
            StudentImportStatus::Uploaded->value,
            StudentImportStatus::Analyzed->value,
            StudentImportStatus::Mapped->value,
            StudentImportStatus::Previewed->value,
            StudentImportStatus::Processing->value,
            StudentImportStatus::Partial->value,
            StudentImportStatus::Failed->value,
        ];

        $storedPath = null;
        try {
            return DB::transaction(function () use ($actor, $academicYearId, $classRoomId, $file, $hash, $schoolId, $activeStatuses, &$storedPath): StudentImportBatch {
                School::query()->whereKey($schoolId)->lockForUpdate()->firstOrFail();

                $activeBatch = StudentImportBatch::withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->whereIn('status', $activeStatuses)
                    ->lockForUpdate()
                    ->first();
                if ($activeBatch) {
                    throw ValidationException::withMessages(['file' => 'Un import de cette école est déjà en cours. Terminez-le ou annulez-le avant d’en lancer un autre.']);
                }

                $storedPath = Storage::disk($this->storage->diskName())->putFileAs(
                    'student-imports',
                    $file,
                    Str::uuid().'.csv',
                    ['visibility' => 'private'],
                );
                if (! $storedPath) {
                    throw new RuntimeException('Le fichier n’a pas pu être enregistré sur le stockage privé.');
                }

                $name = basename($file->getClientOriginalName());
                $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? 'import.csv';

                $batch = StudentImportBatch::create([
                    'school_id' => $schoolId,
                    'academic_year_id' => $academicYearId,
                    'class_room_id' => $classRoomId,
                    'created_by' => $actor->id,
                    'file_name' => Str::limit($name, 160, ''),
                    'file_sha256' => $hash,
                    'file_path' => $storedPath,
                    'status' => StudentImportStatus::Uploaded->value,
                    'expires_at' => now()->addDays(config('student-import.retention_days')),
                ]);

                $this->audit($batch, $actor, 'student_import.uploaded', ['file_name' => $batch->file_name, 'file_sha256' => $hash]);

                return $batch;
            }, 3);
        } catch (\Throwable $exception) {
            if ($storedPath) {
                Storage::disk($this->storage->diskName())->delete($storedPath);
            }

            throw $exception;
        }
    }

    /** @return array<string, mixed> */
    public function analyze(StudentImportBatch $batch, User $actor): array
    {
        $this->authorizeBatch($batch, $actor);
        if ($batch->status !== StudentImportStatus::Uploaded->value) {
            throw new RuntimeException('Ce fichier a déjà été analysé.');
        }

        $path = $this->storedFilePath($batch);
        $analysis = $this->analyzer->analyze($path);
        $fingerprint = StudentCsvAnalyzer::headerFingerprint($analysis['headers']);
        $profile = StudentImportMapping::query()
            ->where('school_id', $batch->school_id)
            ->where('fingerprint', $fingerprint)
            ->first();
        if ($profile) {
            $saved = [];
            foreach ($profile->mapping_json as $field => $columnIndex) {
                if (! array_key_exists($field, config('student-import.synonyms', []))
                    || ! array_key_exists((int) $columnIndex, $analysis['headers'])) {
                    continue;
                }
                $saved[$field] = [
                    'field' => $field,
                    'label' => $analysis['headers'][(int) $columnIndex],
                    'column_index' => (int) $columnIndex,
                    'confidence' => 1.0,
                ];
            }
            if ($saved !== []) {
                $analysis['mapping'] = $saved;
                $analysis['preview'] = $this->analyzer->previewForMapping(
                    $path,
                    $analysis['delimiter'],
                    $analysis['header_row'],
                    $analysis['mapping'],
                );
            }
        }

        $batch->delimiter = $analysis['delimiter'];
        $batch->header_row = $analysis['header_row'];
        $batch->total_rows = $analysis['total_rows'];
        $batch->mapping_json = $analysis['mapping'];
        $batch->mapping_fingerprint = $fingerprint;
        $batch->transitionTo(StudentImportStatus::Analyzed);
        $this->audit($batch, $actor, 'student_import.analyzed', ['rows' => $analysis['total_rows']]);

        return $analysis;
    }

    /** @param array<string, int|string|null> $mapping */
    public function map(StudentImportBatch $batch, User $actor, array $mapping): array
    {
        $this->authorizeBatch($batch, $actor);
        if (! in_array($batch->status, [StudentImportStatus::Analyzed->value, StudentImportStatus::Mapped->value], true)) {
            throw new RuntimeException('Analysez d’abord le fichier avant de modifier les colonnes.');
        }

        $analysis = $this->analyzer->analyze($this->storedFilePath($batch));
        $columns = [];
        foreach ($mapping as $field => $columnIndex) {
            if ($columnIndex === '' || $columnIndex === null) {
                continue;
            }
            if (! array_key_exists($field, config('student-import.synonyms', [])) || ! is_numeric($columnIndex)) {
                throw ValidationException::withMessages(['mapping' => 'Le mapping contient une colonne ou un champ non autorisé.']);
            }

            $index = (int) $columnIndex;
            if (! array_key_exists($index, $analysis['headers'])) {
                throw ValidationException::withMessages(['mapping' => 'Une colonne choisie n’existe plus dans le fichier.']);
            }
            if (isset($columns[$index])) {
                throw ValidationException::withMessages(['mapping' => 'Une colonne ne peut correspondre qu’à un seul champ.']);
            }
            $columns[$index] = true;
        }

        $requiredSets = [
            isset($mapping['first_name']) && $mapping['first_name'] !== '',
            isset($mapping['last_name']) && $mapping['last_name'] !== '',
            isset($mapping['guardian_phone']) && $mapping['guardian_phone'] !== '',
            (isset($mapping['guardian_name']) && $mapping['guardian_name'] !== '')
                || (isset($mapping['guardian_first_name'], $mapping['guardian_last_name'])
                    && $mapping['guardian_first_name'] !== '' && $mapping['guardian_last_name'] !== ''),
        ];
        if (in_array(false, $requiredSets, true)) {
            throw ValidationException::withMessages(['mapping' => 'Associez au minimum les prénoms, le nom et le téléphone du responsable.']);
        }

        $savedMapping = [];
        foreach ($mapping as $field => $columnIndex) {
            if ($columnIndex === '' || $columnIndex === null) {
                continue;
            }
            $index = (int) $columnIndex;
            $savedMapping[$field] = [
                'field' => $field,
                'label' => $analysis['headers'][$index],
                'column_index' => $index,
                'confidence' => 1.0,
            ];
        }

        $batch->mapping_json = $savedMapping;
        $batch->mapping_fingerprint = StudentCsvAnalyzer::headerFingerprint($analysis['headers']);
        $batch->save();
        if ($batch->status !== StudentImportStatus::Mapped->value) {
            $batch->transitionTo(StudentImportStatus::Mapped);
        }

        StudentImportMapping::updateOrCreate(
            ['school_id' => $batch->school_id, 'fingerprint' => $batch->mapping_fingerprint],
            ['created_by' => $actor->id, 'mapping_json' => collect($savedMapping)->mapWithKeys(
                fn (array $column, string $field): array => [$field => $column['column_index']]
            )->all()],
        );

        return $savedMapping;
    }

    /** @return array{total: int, ready: int, warnings: int, errors: int, duplicates: int, ignored: int} */
    public function simulate(StudentImportBatch $batch, User $actor): array
    {
        $this->authorizeBatch($batch, $actor);
        if ($batch->status !== StudentImportStatus::Mapped->value) {
            throw new RuntimeException('Associez les colonnes avant de simuler l’import.');
        }

        $batch->rows()->delete();
        $mapping = $batch->mapping_json ?? [];
        $seenNumbers = [];
        $seenIdentities = [];
        $seenByBirthDate = [];
        $warningCount = 0;

        foreach ($this->analyzer->records($this->storedFilePath($batch), (string) $batch->delimiter, (int) $batch->header_row) as $record) {
            $raw = [];
            foreach ($mapping as $field => $column) {
                $raw[$field] = trim((string) ($record['values'][$column['column_index']] ?? ''));
            }

            if ($this->isSummaryRow($raw)) {
                $batch->rows()->create([
                    'school_id' => $batch->school_id,
                    'row_number' => $record['row'],
                    'status' => 'ignored',
                    'issues_json' => [$this->issue('footer_row', 'row', 'info', 'Ligne de total ignorée.')],
                ]);

                continue;
            }

            $normalized = $this->normalizeRow($raw);
            $issues = $normalized['issues'];
            $data = $normalized['data'];

            if ($data['student_number'] !== null) {
                if (isset($seenNumbers[$data['student_number']])) {
                    $issues[] = $this->issue('duplicate_student_number', 'student_number', 'error', 'Ce matricule apparaît déjà dans ce fichier. Corrigez-le avant de continuer.', $data['student_number']);
                } else {
                    $seenNumbers[$data['student_number']] = true;
                    $existing = Student::withoutGlobalScopes()
                        ->where('school_id', $batch->school_id)
                        ->where('student_number', $data['student_number'])
                        ->first(['id']);
                    if ($existing) {
                        $data['candidate_student_id'] = $existing->id;
                        $issues[] = $this->issue('duplicate_student_number', 'student_number', 'warning', 'Ce matricule existe déjà. Choisissez une action dans la revue des doublons.', $data['student_number']);
                    }
                }
            }

            $identityKey = $this->identityKey($data);
            if ($identityKey !== null && isset($seenIdentities[$identityKey])) {
                $issues[] = $this->issue('duplicate_identity_exact', 'date_of_birth', 'warning', 'Une identité exacte apparaît déjà dans ce fichier. Vérifiez les deux dossiers.');
            } elseif ($identityKey !== null) {
                $seenIdentities[$identityKey] = true;
            }

            $candidate = isset($data['candidate_student_id'])
                ? ['id' => $data['candidate_student_id'], 'kind' => 'exact']
                : $this->findExistingCandidate($batch->school_id, $data);
            if ($candidate) {
                $data['candidate_student_id'] = $candidate['id'];
                if (! collect($issues)->contains(fn (array $issue): bool => str_starts_with($issue['code'], 'duplicate_'))) {
                    $code = $candidate['kind'] === 'approximate' ? 'duplicate_identity_approximate' : 'duplicate_identity_exact';
                    $message = $candidate['kind'] === 'approximate'
                        ? 'Un nom proche apparaît pour la même date de naissance. Vérifiez manuellement.'
                        : 'Un dossier avec le même nom et la même date de naissance existe. Vérifiez avant toute création.';
                    $issues[] = $this->issue($code, 'first_name', 'warning', $message);
                }
            } elseif ($data['date_of_birth'] !== null) {
                foreach ($seenByBirthDate[$data['date_of_birth']] ?? [] as $seen) {
                    if ($this->isApproximateMatch($data, $seen)) {
                        $issues[] = $this->issue('duplicate_identity_approximate', 'last_name', 'warning', 'Un nom proche apparaît pour la même date de naissance. Vérifiez manuellement.');
                        break;
                    }
                }
                $seenByBirthDate[$data['date_of_birth']][] = $data;
            }

            if (collect($issues)->contains(fn (array $issue): bool => $issue['severity'] === 'warning')) {
                $warningCount++;
            }
            $hasErrors = collect($issues)->contains(fn (array $issue): bool => $issue['severity'] === 'error');
            $hasDuplicate = collect($issues)->contains(fn (array $issue): bool => str_starts_with($issue['code'], 'duplicate_'));
            $status = $hasErrors ? 'error' : ($hasDuplicate ? 'needs_review' : 'ready');

            $batch->rows()->create([
                'school_id' => $batch->school_id,
                'row_number' => $record['row'],
                'status' => $status,
                'candidate_student_id' => $data['candidate_student_id'] ?? null,
                'normalized_data' => $data,
                'issues_json' => $issues ?: null,
            ]);
        }

        $counts = $batch->rows()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $batch->failed_count = (int) ($counts['error'] ?? 0);
        $batch->duplicate_count = (int) ($counts['needs_review'] ?? 0);
        $batch->ignored_count = (int) ($counts['ignored'] ?? 0);
        $batch->warning_count = $warningCount;
        $batch->processed_rows = 0;
        $batch->save();
        $batch->transitionTo(StudentImportStatus::Previewed);
        $this->audit($batch, $actor, 'student_import.simulated', [
            'total_rows' => $batch->total_rows,
            'valid_rows' => (int) ($counts['ready'] ?? 0),
            'warning_rows' => $warningCount,
            'error_rows' => $batch->failed_count,
            'duplicate_rows' => $batch->duplicate_count,
        ]);

        return [
            'total' => (int) $batch->total_rows,
            'ready' => (int) ($counts['ready'] ?? 0),
            'warnings' => $batch->warning_count,
            'errors' => $batch->failed_count,
            'duplicates' => $batch->duplicate_count,
            'ignored' => $batch->ignored_count,
        ];
    }

    public function resolveDuplicate(StudentImportRow $row, User $actor, string $decision, ?int $candidateStudentId = null): void
    {
        $batch = $row->batch()->firstOrFail();
        $this->authorizeBatch($batch, $actor);
        if (! in_array($batch->status, [StudentImportStatus::Previewed->value, StudentImportStatus::Partial->value], true)
            || $row->status !== 'needs_review') {
            throw new RuntimeException('Cette ligne n’est pas en attente de revue.');
        }

        if ($decision === 'ignore') {
            $row->decision = 'ignore';
            $row->status = 'ignored';
            $row->normalized_data = null;
            $row->issues_json = [$this->issue('duplicate_ignored', 'row', 'info', 'Ligne ignorée après revue humaine.')];
            $row->save();
            $batch->increment('ignored_count');

            return;
        }

        if ($decision === 'update') {
            $candidate = $candidateStudentId
                ? Student::withoutGlobalScopes()->where('school_id', $batch->school_id)->find($candidateStudentId)
                : ($row->candidate_student_id ? Student::withoutGlobalScopes()->where('school_id', $batch->school_id)->find($row->candidate_student_id) : null);
            if (! $candidate) {
                throw ValidationException::withMessages(['candidate_student_id' => 'Choisissez un élève existant de cette école pour la mise à jour.']);
            }
            $row->candidate_student_id = $candidate->id;
        } elseif ($decision === 'create') {
            $row->candidate_student_id = null;
        } else {
            throw ValidationException::withMessages(['decision' => 'Choisissez créer, mettre à jour ou ignorer.']);
        }

        $row->decision = $decision;
        $row->status = 'ready';
        $row->issues_json = [$this->issue('duplicate_reviewed', 'row', 'info', 'Décision humaine enregistrée.')];
        $row->save();
        $batch->duplicate_count = max(0, (int) $batch->duplicate_count - 1);
        $batch->save();
    }

    public function cancel(StudentImportBatch $batch, User $actor): void
    {
        $this->authorizeBatch($batch, $actor);
        if (! in_array($batch->status, [
            StudentImportStatus::Uploaded->value,
            StudentImportStatus::Analyzed->value,
            StudentImportStatus::Mapped->value,
            StudentImportStatus::Previewed->value,
        ], true) || $batch->created_count > 0 || $batch->updated_count > 0) {
            throw ValidationException::withMessages([
                'batch' => 'Cet import ne peut plus être annulé ici : aucune ligne déjà créée ou modifiée ne sera supprimée.',
            ]);
        }

        $filePath = $batch->file_path;
        DB::transaction(function () use ($batch, $actor): void {
            $lockedBatch = StudentImportBatch::withoutGlobalScopes()
                ->where('school_id', $batch->school_id)
                ->lockForUpdate()
                ->findOrFail($batch->id);
            if (! in_array($lockedBatch->status, [
                StudentImportStatus::Uploaded->value,
                StudentImportStatus::Analyzed->value,
                StudentImportStatus::Mapped->value,
                StudentImportStatus::Previewed->value,
            ], true) || $lockedBatch->created_count > 0 || $lockedBatch->updated_count > 0) {
                throw ValidationException::withMessages([
                    'batch' => 'Cet import ne peut plus être annulé ici : aucune ligne déjà créée ou modifiée ne sera supprimée.',
                ]);
            }

            $lockedBatch->rows()->delete();
            $lockedBatch->transitionTo(StudentImportStatus::Cancelled);
            $this->audit($lockedBatch, $actor, 'student_import.cancelled', [
                'file_sha256' => $lockedBatch->file_sha256,
            ]);
        });

        if ($filePath && Storage::disk($this->storage->diskName())->exists($filePath)
            && ! Storage::disk($this->storage->diskName())->delete($filePath)) {
            throw new RuntimeException('L’import est annulé, mais son fichier privé n’a pas pu être supprimé. La purge planifiée réessaiera.');
        }

        $batch->file_path = null;
        $batch->expires_at = null;
        $batch->save();
    }

    /** @return array{status: string, processed: int, remaining: int} */
    public function processNextChunk(StudentImportBatch $batch, User $actor): array
    {
        $this->authorizeBatch($batch, $actor);
        $batch = DB::transaction(function () use ($batch): StudentImportBatch {
            $lockedBatch = StudentImportBatch::withoutGlobalScopes()
                ->where('school_id', $batch->school_id)
                ->lockForUpdate()
                ->findOrFail($batch->id);
            if ($lockedBatch->status === StudentImportStatus::Completed->value) {
                return $lockedBatch;
            }
            if (! in_array($lockedBatch->status, [
                StudentImportStatus::Previewed->value,
                StudentImportStatus::Partial->value,
                StudentImportStatus::Failed->value,
                StudentImportStatus::Processing->value,
            ], true)) {
                throw new RuntimeException('Confirmez d’abord la simulation de cet import.');
            }
            if ($lockedBatch->status !== StudentImportStatus::Processing->value) {
                $lockedBatch->started_at ??= now();
                $lockedBatch->save();
                $lockedBatch->transitionTo(StudentImportStatus::Processing);
            }

            return $lockedBatch;
        }, 3);
        if ($batch->status === StudentImportStatus::Completed->value) {
            return ['status' => $batch->status, 'processed' => 0, 'remaining' => 0];
        }

        $rows = $batch->rows()->where('status', 'ready')->orderBy('row_number')
            ->limit(config('student-import.chunk_size', 100))
            ->get();

        $done = 0;
        foreach ($rows as $row) {
            try {
                if ($this->processRow($batch, $row)) {
                    $done++;
                }
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintFailure($exception)) {
                    throw $exception;
                }
                $row->refresh();
                $row->status = 'error';
                $row->issues_json = [$this->issue('matricule_collision', 'student_number', 'error', 'Le matricule a été pris par un autre dossier pendant l’import. Vérifiez la ligne avant de reprendre.')];
                $row->normalized_data = null;
                $row->save();
                $batch->increment('failed_count');
                $batch->increment('processed_rows');
            } catch (ValidationException $exception) {
                $row->refresh();
                $row->status = 'error';
                $row->issues_json = [$this->issue('business_rule_rejected', 'row', 'error', 'Une règle de scolarité a refusé cette ligne. Vérifiez l’année, la classe ou la période d’inscription.')];
                $row->normalized_data = null;
                $row->save();
                $batch->increment('failed_count');
                $batch->increment('processed_rows');
            }
        }

        $remaining = $batch->rows()->where('status', 'ready')->count();
        $needsReview = $batch->rows()->where('status', 'needs_review')->exists();
        if ($remaining > 0) {
            $batch->refresh();
            $batch->processed_rows = $batch->rows()->whereIn('status', ['created', 'updated', 'ignored', 'error'])->count();
            $batch->save();

            return ['status' => $batch->status, 'processed' => $done, 'remaining' => $remaining];
        }

        if ($needsReview) {
            $batch->refresh();
            $batch->processed_rows = $batch->rows()->whereIn('status', ['created', 'updated', 'ignored', 'error'])->count();
            $batch->save();
            $batch->transitionTo(StudentImportStatus::Partial);

            return ['status' => $batch->status, 'processed' => $done, 'remaining' => 0];
        }

        $batch->refresh();
        $batch->processed_rows = $batch->rows()->whereIn('status', ['created', 'updated', 'ignored', 'error'])->count();
        $batch->completed_at = now();
        $batch->save();
        $batch->transitionTo(StudentImportStatus::Completed);
        $this->audit($batch, $actor, 'student_import.completed', [
            'created' => $batch->created_count,
            'updated' => $batch->updated_count,
            'ignored' => $batch->ignored_count,
            'errors' => $batch->failed_count,
        ]);

        return ['status' => $batch->status, 'processed' => $done, 'remaining' => 0];
    }

    private function processRow(StudentImportBatch $batch, StudentImportRow $row): bool
    {
        return DB::transaction(function () use ($batch, $row): bool {
            $lockedRow = StudentImportRow::query()->where('student_import_batch_id', $batch->id)
                ->where('school_id', $batch->school_id)
                ->whereKey($row->id)
                ->lockForUpdate()
                ->firstOrFail();
            if ($lockedRow->status !== 'ready') {
                return false;
            }

            $data = $lockedRow->normalized_data;
            if (! is_array($data)) {
                throw new RuntimeException('Les données de reprise de cette ligne sont absentes.');
            }

            $studentAttributes = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => $data['middle_name'] ?: null,
                'date_of_birth' => $data['date_of_birth'],
                'gender' => $data['gender'],
                'active' => true,
                'status' => 'active',
            ];

            if ($lockedRow->decision === 'update' && $lockedRow->candidate_student_id) {
                $student = Student::withoutGlobalScopes()
                    ->where('school_id', $batch->school_id)
                    ->lockForUpdate()
                    ->findOrFail($lockedRow->candidate_student_id);
                $student->update($studentAttributes);
                $resultStatus = 'updated';
            } else {
                $student = Student::create([
                    ...$studentAttributes,
                    'school_id' => $batch->school_id,
                    'student_number' => $data['student_number'],
                    'admission_number' => $data['student_number'],
                ]);
                $resultStatus = 'created';
            }

            $guardian = Guardian::create([
                'school_id' => $batch->school_id,
                'first_name' => $data['guardian_first_name'],
                'last_name' => $data['guardian_last_name'],
                'name' => $data['guardian_name'],
                'relationship' => $data['guardian_relationship'] ?: 'Parent',
                'phone' => $data['guardian_phone'],
                'active' => true,
            ]);
            $student->guardians()->syncWithoutDetaching([$guardian->id => [
                'is_primary' => ! $student->guardians()->exists(),
                'receives_sms' => true,
                'receives_whatsapp' => false,
            ]]);

            $alreadyEnrolled = $student->enrollments()
                ->where('academic_year_id', $batch->academic_year_id)
                ->first();
            if ($alreadyEnrolled && (int) $alreadyEnrolled->class_room_id !== (int) $batch->class_room_id) {
                throw ValidationException::withMessages(['class_room_id' => 'Cet élève est déjà inscrit dans une autre classe pour cette année.']);
            }
            if (! $alreadyEnrolled) {
                $date = today()->toDateString();
                $student->enrollments()->create([
                    'school_id' => $batch->school_id,
                    'academic_year_id' => $batch->academic_year_id,
                    'class_room_id' => $batch->class_room_id,
                    'enrollment_date' => $date,
                    'enrolled_at' => $date,
                    'status' => 'active',
                ]);
            }

            $lockedRow->student_id = $student->id;
            $lockedRow->status = $resultStatus;
            $lockedRow->normalized_data = null;
            $lockedRow->save();
            $batch->increment($resultStatus === 'created' ? 'created_count' : 'updated_count');
            $batch->increment('processed_rows');

            return true;
        }, 3);
    }

    /** @return array{data: array<string, mixed>, issues: array<int, array<string, mixed>>} */
    private function normalizeRow(array $raw): array
    {
        $firstName = StudentImportNormalizer::normalizeName((string) ($raw['first_name'] ?? ''));
        $lastName = StudentImportNormalizer::normalizeName((string) ($raw['last_name'] ?? ''));
        $middleName = StudentImportNormalizer::normalizeName((string) ($raw['middle_name'] ?? ''));
        $gender = StudentImportNormalizer::normalizeGender((string) ($raw['gender'] ?? ''));
        $birthDate = StudentImportNormalizer::normalizeDate((string) ($raw['date_of_birth'] ?? ''));
        $phone = StudentImportNormalizer::normalizePhone((string) ($raw['guardian_phone'] ?? ''));

        $guardianName = StudentImportNormalizer::normalizeName((string) ($raw['guardian_name'] ?? ''));
        $guardianFirst = StudentImportNormalizer::normalizeName((string) ($raw['guardian_first_name'] ?? ''));
        $guardianLast = StudentImportNormalizer::normalizeName((string) ($raw['guardian_last_name'] ?? ''));
        if ($guardianName === '' && ($guardianFirst !== '' || $guardianLast !== '')) {
            $guardianName = trim($guardianFirst.' '.$guardianLast);
        } elseif ($guardianName !== '' && $guardianFirst === '' && $guardianLast === '') {
            $parts = preg_split('/\s+/u', $guardianName, 2) ?: [];
            $guardianFirst = $parts[0] ?? '';
            $guardianLast = $parts[1] ?? '';
        }

        $issues = [];
        if ($firstName === '') {
            $issues[] = $this->issue('first_name_required', 'first_name', 'error', 'Le prénom est obligatoire.', $raw['first_name'] ?? '');
        }
        if ($lastName === '') {
            $issues[] = $this->issue('last_name_required', 'last_name', 'error', 'Le nom est obligatoire.', $raw['last_name'] ?? '');
        }
        if (($raw['date_of_birth'] ?? '') !== '' && $birthDate['error']) {
            $issues[] = $this->issue($birthDate['error'], 'date_of_birth', 'error', 'La date de naissance est illisible ou impossible. Utilisez jj/mm/aaaa ou aaaa-mm-jj.', $raw['date_of_birth']);
        }
        if ($birthDate['warning']) {
            $issues[] = $this->issue($birthDate['warning'], 'date_of_birth', 'warning', 'L’année à deux chiffres est ambiguë. Corrigez-la avec une année à quatre chiffres.', $raw['date_of_birth']);
        }
        if ($gender['warning']) {
            $issues[] = $this->issue($gender['warning'], 'gender', 'warning', 'Le sexe n’a pas été reconnu; vérifiez la valeur ou laissez-la vide.', $raw['gender'] ?? '');
        }
        if ($phone['error']) {
            $message = $phone['error'] === 'phone_multiple_numbers'
                ? 'Plusieurs numéros sont dans cette cellule. Choisissez un numéro principal avant de continuer.'
                : 'Le téléphone du responsable est absent ou invalide. Corrigez-le au format local ou +228.';
            $issues[] = $this->issue($phone['error'], 'guardian_phone', 'error', $message, $raw['guardian_phone'] ?? '');
        }
        if ($guardianName === '') {
            $issues[] = $this->issue('guardian_name_required', 'guardian_name', 'error', 'Le nom du responsable est obligatoire.', $raw['guardian_name'] ?? '');
        }

        return [
            'data' => [
                'student_number' => trim((string) ($raw['student_number'] ?? '')) ?: null,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'middle_name' => $middleName,
                'date_of_birth' => $birthDate['value'],
                'gender' => $gender['value'],
                'guardian_name' => $guardianName,
                'guardian_first_name' => $guardianFirst ?: null,
                'guardian_last_name' => $guardianLast ?: null,
                'guardian_relationship' => trim((string) ($raw['guardian_relationship'] ?? '')),
                'guardian_phone' => $phone['value'],
            ],
            'issues' => $issues,
        ];
    }

    /** @return array<string, mixed>|null */
    /** @return array{id: int, kind: 'exact'|'approximate'}|null */
    private function findExistingCandidate(int $schoolId, array $data): ?array
    {
        if (! $data['date_of_birth']) {
            return null;
        }

        $students = Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->whereDate('date_of_birth', $data['date_of_birth'])
            ->get(['id', 'first_name', 'last_name']);

        foreach ($students as $student) {
            $first = $this->comparisonName($student->first_name);
            $last = $this->comparisonName($student->last_name);
            $targetFirst = $this->comparisonName($data['first_name']);
            $targetLast = $this->comparisonName($data['last_name']);

            if ($first === $targetFirst && $last === $targetLast) {
                return ['id' => (int) $student->id, 'kind' => 'exact'];
            }
            if ($first === $targetFirst && levenshtein($last, $targetLast) <= 2) {
                return ['id' => (int) $student->id, 'kind' => 'approximate'];
            }
        }

        return null;
    }

    private function isApproximateMatch(array $data, array $candidate): bool
    {
        return $data['first_name'] !== ''
            && $this->comparisonName($data['first_name']) === $this->comparisonName($candidate['first_name'])
            && levenshtein($this->comparisonName($data['last_name']), $this->comparisonName($candidate['last_name'])) <= 2;
    }

    private function comparisonName(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', mb_strtolower(trim($value)));

        return strtolower($ascii === false ? $value : $ascii);
    }

    private function identityKey(array $data): ?string
    {
        if (! $data['date_of_birth'] || $data['first_name'] === '' || $data['last_name'] === '') {
            return null;
        }

        return hash('sha256', implode('|', [
            $this->comparisonName($data['first_name']),
            $this->comparisonName($data['last_name']),
            $data['date_of_birth'],
        ]));
    }

    private function isSummaryRow(array $raw): bool
    {
        foreach (['student_number', 'last_name', 'first_name'] as $field) {
            $value = mb_strtolower(trim((string) ($raw[$field] ?? '')));
            if ($value !== '' && in_array($value, ['total', 'total général', 'effectif', 'sous-total'], true)) {
                return true;
            }
        }

        return false;
    }

    private function issue(string $code, string $field, string $severity, string $message, ?string $value = null): array
    {
        return array_filter([
            'code' => $code,
            'field' => $field,
            'severity' => $severity,
            'message' => $message,
            'value' => $value,
        ], static fn (mixed $item): bool => $item !== null);
    }

    private function authorizeBatch(StudentImportBatch $batch, User $actor): void
    {
        if ($batch->school_id !== $this->schoolIdFor($actor)) {
            throw new AuthorizationException('Ce lot n’est pas accessible depuis cette école.');
        }
    }

    private function schoolIdFor(User $actor): int
    {
        if ($actor->isDirector() || $actor->isSecretary()) {
            if (! $actor->school_id) {
                throw new AuthorizationException('Le compte n’est associé à aucune école.');
            }

            return (int) $actor->school_id;
        }
        if ($actor->isSuperAdmin() && $actor->hasActiveSchoolIntervention()) {
            return (int) app(SchoolContext::class)->id();
        }

        throw new AuthorizationException('Vous n’êtes pas autorisé à importer les élèves.');
    }

    private function storedFilePath(StudentImportBatch $batch): string
    {
        if (! $batch->file_path) {
            throw new RuntimeException('Le fichier privé de cet import n’est plus disponible.');
        }

        return $this->storage->localPath($batch->file_path);
    }

    private function audit(StudentImportBatch $batch, User $actor, string $action, array $details): void
    {
        AuditLog::create([
            'school_id' => $batch->school_id,
            'user_id' => $actor->id,
            'action' => $action,
            'auditable_type' => StudentImportBatch::class,
            'auditable_id' => $batch->id,
            'old_values' => null,
            'new_values' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    private function isUniqueConstraintFailure(QueryException $exception): bool
    {
        return in_array($exception->getCode(), ['23000', '23505'], true)
            || str_contains(strtolower($exception->getMessage()), 'unique constraint');
    }
}
