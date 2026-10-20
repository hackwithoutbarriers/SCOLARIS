<?php

namespace App\Filament\Pages;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\StudentImportBatch;
use App\Models\StudentImportRow;
use App\Services\StudentCsvAnalyzer;
use App\Services\StudentImportBatchService;
use App\Services\StudentImportReportService;
use App\Services\StudentImportStorage;
use App\Support\Tenancy\SchoolContext;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentImport extends Page implements HasForms
{
    use InteractsWithForms;
    use WithPagination;

    protected static ?string $navigationGroup = 'Élèves et familles';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Importer des élèves';

    protected static ?string $title = 'Importer et inscrire des élèves';

    protected static string $view = 'filament.pages.student-import';

    public ?array $data = [];

    public array $analysis = [];

    public array $simulation = [];

    public string $rowFilter = 'all';

    #[Locked]
    public ?int $batchId = null;

    public static function canAccess(): bool
    {
        return Gate::allows('viewAny', StudentImportBatch::class);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $batchId = request()->integer('batch');
        if ($batchId > 0) {
            $this->loadBatch(StudentImportBatch::query()->findOrFail($batchId));
        } else {
            $schoolId = app(SchoolContext::class)->id() ?? auth()->user()?->school_id;
            if ($schoolId !== null) {
                $activeBatch = StudentImportBatch::query()
                    ->where('school_id', $schoolId)
                    ->whereIn('status', ['uploaded', 'analyzed', 'mapped', 'previewed', 'processing', 'partial', 'failed'])
                    ->latest('created_at')
                    ->latest('id')
                    ->first();

                if ($activeBatch) {
                    $this->loadBatch($activeBatch);
                }
            }
        }

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        $schema = [
            Section::make('Fichier et inscription')
                ->description('Importez un CSV UTF-8 pour une année et une classe de votre école.')
                ->schema([
                    FileUpload::make('file')
                        ->label('Fichier CSV')
                        ->helperText('CSV UTF-8 uniquement, 5 Mo maximum. XLSX n’est pas pris en charge dans cette étape.')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv'])
                        ->maxSize((int) ceil(config('student-import.max_file_size') / 1024))
                        ->disk(config('student-import.disk', 'local'))
                        ->directory('student-imports/uploads')
                        ->visibility('private')
                        ->required($this->batchId === null)
                        ->disabled($this->batchId !== null),
                    Select::make('academic_year_id')
                        ->label('Année scolaire active')
                        ->options(fn (): array => AcademicYear::query()
                            ->where(fn ($query) => $query->where('status', 'active')->orWhere('is_current', true))
                            ->orderByDesc('start_date')->pluck('name', 'id')->all())
                        ->required()
                        ->disabled($this->batchId !== null)
                        ->live(),
                    Select::make('class_room_id')
                        ->label('Classe de destination')
                        ->options(fn (): array => ClassRoom::query()
                            ->when($this->data['academic_year_id'] ?? null, fn ($query, $yearId) => $query->where('academic_year_id', $yearId), fn ($query) => $query->whereRaw('1 = 0'))
                            ->orderBy('name')->pluck('name', 'id')->all())
                        ->required()
                        ->disabled($this->batchId !== null || ! ($this->data['academic_year_id'] ?? null)),
                ])->columns(2),
        ];

        if ($this->analysis !== [] && $this->batchId !== null) {
            $options = [];
            foreach ($this->analysis['headers'] as $index => $header) {
                $options[$index] = $header === '' ? 'Colonne '.($index + 1).' (sans titre)' : $header;
            }

            $fields = [
                'student_number' => 'Matricule (facultatif)',
                'last_name' => 'Nom de l’élève',
                'first_name' => 'Prénom(s)',
                'middle_name' => 'Deuxième prénom (facultatif)',
                'date_of_birth' => 'Date de naissance (facultatif)',
                'gender' => 'Sexe (facultatif)',
                'guardian_name' => 'Nom du responsable',
                'guardian_first_name' => 'Prénom du responsable (optionnel si nom complet fourni)',
                'guardian_last_name' => 'Nom du responsable (optionnel si nom complet fourni)',
                'guardian_phone' => 'Téléphone du responsable',
                'guardian_relationship' => 'Lien avec l’élève (facultatif)',
            ];

            $mappingSchema = [];
            foreach ($fields as $field => $label) {
                $default = $this->analysis['mapping'][$field]['column_index'] ?? null;
                $confidence = $this->analysis['mapping'][$field]['confidence'] ?? null;
                $mappingSchema[] = Select::make('mapping.'.$field)
                    ->label($label)
                    ->options($options)
                    ->placeholder('Ignorer cette colonne')
                    ->helperText($confidence === null
                        ? 'Aucune correspondance proposée; choisissez une colonne si elle convient.'
                        : 'Proposition automatique — confiance '.(int) round($confidence * 100).' %. Vérifiez avant de continuer.')
                    ->default($default)
                    ->required(in_array($field, ['last_name', 'first_name', 'guardian_phone'], true));
            }
            $schema[] = Section::make('Vérifier les colonnes')
                ->description('Vérifiez les correspondances proposées. Les colonnes non associées sont ignorées.')
                ->schema($mappingSchema)->columns(2);
        }

        return $form->schema($schema)->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('analyze')
                ->label('Analyser le fichier')
                ->color('primary')
                ->visible(fn (): bool => $this->batchId === null)
                ->action('analyze'),
            Action::make('template')
                ->label('Télécharger le modèle CSV')
                ->color('gray')
                ->action('downloadTemplate'),
            Action::make('simulate')
                ->label('Vérifier et simuler')
                ->color('warning')
                ->visible(fn (): bool => $this->batchId !== null && in_array($this->currentBatch()?->status, ['analyzed', 'mapped'], true))
                ->action('simulate'),
            Action::make('process')
                ->label(fn (): string => in_array($this->currentBatch()?->status, ['partial', 'processing'], true)
                    || $this->currentBatch()?->status === 'failed'
                    ? 'Reprendre le traitement'
                    : 'Confirmer et traiter un lot')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(fn (): string => $this->currentBatch()?->status === 'failed'
                    ? 'Reprendre le traitement interrompu'
                    : 'Confirmer l’import simulé')
                ->modalDescription(fn (): string => $this->currentBatch()?->status === 'failed'
                    ? 'Les lignes déjà traitées ne seront pas rejouées. Les lignes restantes seront reprises par lots.'
                    : 'Les lignes valides seront inscrites dans l’année et la classe choisies. Les doublons à vérifier restent en attente.')
                ->visible(fn (): bool => $this->batchId !== null && in_array($this->currentBatch()?->status, ['previewed', 'partial', 'processing', 'failed'], true))
                ->action('processNextChunk'),
            Action::make('report')
                ->label('Télécharger le rapport')
                ->color('gray')
                ->visible(fn (): bool => $this->batchId !== null && $this->currentBatch()?->status !== 'uploaded')
                ->action('downloadReport'),
            Action::make('cancel')
                ->label('Annuler cet import')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Annuler cet import ?')
                ->modalDescription('Aucun élève déjà créé ou modifié ne sera supprimé. Le fichier et les résultats de simulation seront effacés.')
                ->visible(fn (): bool => in_array($this->currentBatch()?->status, ['uploaded', 'analyzed', 'mapped', 'previewed'], true))
                ->action('cancelImport'),
            Action::make('newImport')
                ->label('Nouvel import')
                ->color('gray')
                ->visible(fn (): bool => in_array($this->currentBatch()?->status, ['completed', 'cancelled'], true))
                ->action('newImport'),
            Action::make('dashboard')
                ->label('Retour au tableau de bord')
                ->icon('heroicon-o-arrow-left')
                ->url(Dashboard::getUrl()),
        ];
    }

    public function analyze(StudentImportBatchService $service, StudentCsvAnalyzer $analyzer): void
    {
        $state = $this->form->getState();
        $storedUpload = $state['file'] ?? null;
        $normalizedPath = str_replace('\\', '/', (string) $storedUpload);
        if (! str_starts_with($normalizedPath, 'student-imports/uploads/')
            || str_contains($normalizedPath, '..')
            || ! Storage::disk(config('student-import.disk', 'local'))->exists($normalizedPath)) {
            throw new \RuntimeException('Le fichier temporaire n’est plus disponible. Téléversez-le à nouveau.');
        }

        $storage = app(StudentImportStorage::class);
        $path = $storage->localPath($normalizedPath);
        $file = new UploadedFile($path, basename($normalizedPath), mime_content_type($path) ?: 'text/plain', null, true);
        try {
            $batch = $service->create($file, auth()->user(), (int) $state['academic_year_id'], (int) $state['class_room_id']);
        } catch (InvalidArgumentException $exception) {
            Notification::make()->danger()->title('Fichier à corriger')->body($exception->getMessage())->send();

            return;
        } finally {
            $disk = Storage::disk(config('student-import.disk', 'local'));
            if ($disk->exists($normalizedPath) && ! $disk->delete($normalizedPath)) {
                throw new \RuntimeException('Le fichier temporaire privé n’a pas pu être supprimé.');
            }
        }
        $this->batchId = $batch->id;

        try {
            if ($batch->status === 'uploaded') {
                $this->analysis = $service->analyze($batch, auth()->user());
            } elseif ($batch->file_path) {
                $storage = app(StudentImportStorage::class);
                $this->analysis = $analyzer->analyze($storage->localPath($batch->file_path));
            }
        } catch (InvalidArgumentException $exception) {
            $this->analysis = [];
            Notification::make()->danger()->title('Fichier à corriger')->body($exception->getMessage())->send();

            return;
        }

        $this->data = [
            ...$state,
            'file' => $batch->file_path,
            'mapping' => collect($this->analysis['mapping'] ?? [])->mapWithKeys(
                fn (array $column, string $field): array => [$field => $column['column_index']]
            )->all(),
        ];
        $this->form->fill($this->data);
        Notification::make()->success()->title('Analyse prête')->body('Vérifiez les colonnes proposées avant de lancer la simulation.')->send();
    }

    public function downloadTemplate(): StreamedResponse
    {
        Gate::authorize('viewAny', StudentImportBatch::class);

        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                throw new \RuntimeException('Le modèle CSV n’a pas pu être généré.');
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Matricule',
                'Nom',
                'Prénom',
                'Date de naissance',
                'Sexe',
                'Tél parent',
                'Responsable',
            ], ';', '"', '');
            fputcsv($stream, [
                'EXEMPLE-001',
                'NOM FICTIF',
                'Prénom fictif',
                '12/05/2014',
                'F',
                '90000000',
                'Responsable fictif',
            ], ';', '"', '');
            fclose($stream);
        }, 'modele-import-eleves.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function simulate(StudentImportBatchService $service): void
    {
        $batch = $this->authorizedBatch();
        $state = $this->form->getState();
        $mapping = $service->map($batch, auth()->user(), $state['mapping'] ?? []);
        $this->analysis['mapping'] = $mapping;
        $storage = app(StudentImportStorage::class);
        $this->analysis['preview'] = app(StudentCsvAnalyzer::class)->previewForMapping(
            $storage->localPath($batch->file_path),
            (string) $batch->delimiter,
            (int) $batch->header_row,
            $mapping,
        );
        $this->simulation = $service->simulate($batch->fresh(), auth()->user());
        $this->resetPage();

        Notification::make()->success()->title('Simulation terminée')
            ->body("{$this->simulation['ready']} ligne(s) prête(s), {$this->simulation['errors']} erreur(s), {$this->simulation['duplicates']} doublon(s) à vérifier.")
            ->send();
    }

    public function processNextChunk(StudentImportBatchService $service): void
    {
        $batch = $this->authorizedBatch();
        $result = $service->processNextChunk($batch, auth()->user());
        $this->simulation = [
            'ready' => $batch->fresh()->rows()->where('status', 'ready')->count(),
            'errors' => $batch->fresh()->failed_count,
            'duplicates' => $batch->fresh()->rows()->where('status', 'needs_review')->count(),
        ];

        if ($result['status'] === 'completed') {
            Notification::make()->success()->title('Import terminé')
                ->body("{$batch->fresh()->created_count} élève(s) créé(s), {$batch->fresh()->updated_count} mis à jour, {$batch->fresh()->failed_count} ligne(s) en erreur.")
                ->send();
        } else {
            Notification::make()->info()->title('Traitement enregistré')
                ->body("{$batch->fresh()->processed_rows} ligne(s) traitée(s); {$result['remaining']} ligne(s) prête(s) restent à traiter.")
                ->send();
        }
    }

    public function resolveRow(int $rowId, string $decision, StudentImportBatchService $service): void
    {
        $row = StudentImportRow::query()->where('student_import_batch_id', $this->authorizedBatch()->id)->findOrFail($rowId);
        Gate::authorize('update', $row);
        $service->resolveDuplicate($row, auth()->user(), $decision);
        Notification::make()->success()->title('Décision enregistrée')->send();
    }

    public function downloadReport(StudentImportReportService $reports): StreamedResponse
    {
        $batch = $this->authorizedBatch();
        Gate::authorize('view', $batch);

        return $reports->download($batch);
    }

    public function cancelImport(StudentImportBatchService $service): void
    {
        $batch = $this->authorizedBatch();
        $service->cancel($batch, auth()->user());
        $this->analysis = [];
        $this->simulation = [];
        Notification::make()->success()->title('Import annulé')
            ->body('Le fichier privé et les résultats de simulation ont été supprimés.')
            ->send();
    }

    public function newImport(): void
    {
        abort_unless(in_array($this->currentBatch()?->status, ['completed', 'cancelled'], true), 403);
        $this->batchId = null;
        $this->analysis = [];
        $this->simulation = [];
        $this->rowFilter = 'all';
        $this->data = [];
        $this->form->fill([]);
        $this->resetPage();
    }

    public function rows(): LengthAwarePaginator
    {
        if ($this->batchId === null) {
            return StudentImportRow::query()->whereRaw('1 = 0')->paginate(20);
        }

        $query = StudentImportRow::query()
            ->where('student_import_batch_id', $this->batchId)
            ->with('candidateStudent')
            ->orderBy('row_number');

        switch ($this->rowFilter) {
            case 'ready':
                $query->where('status', 'ready');
                break;
            case 'warnings':
                $this->applyWarningFilter($query);
                break;
            case 'errors':
                $query->where('status', 'error');
                break;
            case 'duplicates':
                $query->where('status', 'needs_review');
                break;
        }

        return $query->paginate(20);
    }

    public function updatedRowFilter(): void
    {
        $this->resetPage();
    }

    private function applyWarningFilter($query): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            $query->whereRaw("EXISTS (SELECT 1 FROM json_each(student_import_rows.issues_json) AS issue WHERE json_extract(issue.value, '$.severity') = ?)", ['warning']);

            return;
        }
        if ($driver === 'pgsql') {
            $query->whereRaw('student_import_rows.issues_json::jsonb @> ?::jsonb', [json_encode([['severity' => 'warning']])]);

            return;
        }
        if ($driver === 'mysql') {
            $query->whereRaw("JSON_CONTAINS(student_import_rows.issues_json, ?, '$')", [json_encode(['severity' => 'warning'])]);

            return;
        }

        throw new \RuntimeException('Le filtre des avertissements n’est pas pris en charge par ce moteur de base de données.');
    }

    public function currentBatch(): ?StudentImportBatch
    {
        if ($this->batchId === null) {
            return null;
        }

        $schoolId = app(SchoolContext::class)->id() ?? auth()->user()?->school_id;

        return StudentImportBatch::query()->where('school_id', $schoolId)->find($this->batchId);
    }

    private function authorizedBatch(): StudentImportBatch
    {
        $batch = $this->currentBatch();
        abort_unless($batch, 404);
        Gate::authorize('update', $batch);

        return $batch;
    }

    private function loadBatch(StudentImportBatch $batch): void
    {
        Gate::authorize('view', $batch);
        $this->batchId = $batch->id;
        if ($batch->file_path) {
            $storage = app(StudentImportStorage::class);
            $this->analysis = app(StudentCsvAnalyzer::class)->analyze(
                $storage->localPath($batch->file_path)
            );
            $savedMapping = $batch->mapping_json ?? [];
            if ($savedMapping !== []) {
                $this->analysis['mapping'] = $savedMapping;
                $this->analysis['preview'] = app(StudentCsvAnalyzer::class)->previewForMapping(
                    $storage->localPath($batch->file_path),
                    (string) $batch->delimiter,
                    (int) $batch->header_row,
                    $savedMapping,
                );
            }
            $this->data['mapping'] = collect($batch->mapping_json ?? [])->mapWithKeys(
                fn (array $column, string $field): array => [$field => $column['column_index']]
            )->all();
        }
        if (in_array($batch->status, ['previewed', 'processing', 'partial', 'completed', 'failed'], true)) {
            $this->simulation = [
                'ready' => $batch->rows()->where('status', 'ready')->count(),
                'warnings' => $batch->warning_count,
                'errors' => $batch->rows()->where('status', 'error')->count(),
                'duplicates' => $batch->rows()->where('status', 'needs_review')->count(),
                'ignored' => $batch->rows()->where('status', 'ignored')->count(),
            ];
        }
        $this->data = [
            ...$this->data,
            'academic_year_id' => $batch->academic_year_id,
            'class_room_id' => $batch->class_room_id,
            'file' => $batch->file_path,
        ];
    }
}
