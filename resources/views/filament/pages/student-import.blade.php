<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @if ($this->currentBatch())
            @php($batch = $this->currentBatch())
            <x-filament::section>
                <x-slot name="heading">État du lot</x-slot>
                @php($progress = $batch->total_rows > 0 ? min(100, (int) floor($batch->processed_rows / $batch->total_rows * 100)) : 0)
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Statut : <strong>{{ match ($batch->status) {
                        'uploaded' => 'Fichier reçu',
                        'analyzed' => 'Analyse terminée',
                        'mapped' => 'Colonnes associées',
                        'previewed' => 'Simulation prête',
                        'processing' => 'Traitement en cours',
                        'partial' => 'Revue à terminer',
                        'completed' => 'Import terminé',
                        'failed' => 'Traitement interrompu',
                        'cancelled' => 'Import annulé',
                        default => 'En attente',
                    } }}</strong>
                    · {{ $batch->processed_rows }} / {{ $batch->total_rows }} lignes parcourues
                    · {{ $batch->created_count }} créées
                    · {{ $batch->updated_count }} mises à jour
                    · {{ $batch->failed_count }} erreurs
                    · {{ $batch->rows()->where('status', 'needs_review')->count() }} doublons à vérifier
                </p>
                @if (in_array($batch->status, ['processing', 'partial', 'completed', 'failed'], true))
                    <div class="mt-3" role="progressbar" aria-label="Progression de l’import" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}">
                        <div class="h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full rounded-full bg-primary-600 transition-all" style="width: {{ $progress }}%"></div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $progress }} % parcourus</p>
                    </div>
                @endif
                @if ($batch->status === 'processing')
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Le traitement est enregistré par lots. Vous pouvez reprendre ici après une coupure.</p>
                @endif
                @if ($batch->status === 'failed')
                    <p class="mt-2 text-sm text-warning-700 dark:text-warning-300">Le traitement a été interrompu. Reprenez-le pour continuer les lignes restantes; les lignes déjà traitées ne seront pas rejouées.</p>
                @endif
            </x-filament::section>

            @if ($this->simulation !== [])
                <x-filament::section>
                    @if ($batch->status === 'completed')
                        <x-slot name="heading">Résultat de l’import</x-slot>
                        <p class="text-sm text-gray-700 dark:text-gray-200">
                            {{ $batch->created_count }} élève(s) créé(s),
                            {{ $batch->updated_count }} mis à jour,
                            {{ $batch->ignored_count }} ignoré(s),
                            {{ $batch->failed_count }} en erreur.
                        </p>
                    @else
                        <x-slot name="heading">Impact prévu / restant</x-slot>
                        <p class="text-sm text-gray-700 dark:text-gray-200">
                            {{ $this->simulation['ready'] ?? 0 }} ligne(s) prête(s) à traiter,
                            {{ $this->simulation['duplicates'] ?? 0 }} doublon(s) à vérifier,
                            {{ $this->simulation['errors'] ?? 0 }} erreur(s),
                            {{ $this->simulation['ignored'] ?? 0 }} ligne(s) ignorée(s).
                        </p>
                    @endif
                </x-filament::section>
            @endif

            @if ($this->analysis !== [])
                <x-filament::section>
                    <x-slot name="heading">Aperçu du fichier (20 premières lignes maximum)</x-slot>
                    @if (! empty($this->analysis['ignored_columns']))
                        <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">Colonnes non reconnues automatiquement : {{ implode(', ', $this->analysis['ignored_columns']) }}</p>
                    @endif
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="p-2">Ligne</th>
                                    @foreach (array_keys($this->analysis['preview'][0]['values'] ?? []) as $field)
                                        <th class="p-2">{{ str_replace('_', ' ', $field) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->analysis['preview'] as $previewRow)
                                    <tr class="border-b">
                                        <td class="p-2">{{ $previewRow['row'] }}</td>
                                        @foreach ($previewRow['values'] as $value)
                                            <td class="p-2">{{ $value }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            @endif

            @if ($batch->status === 'previewed' || $batch->status === 'partial' || $batch->status === 'completed')
                <x-filament::section>
                    <x-slot name="heading">Lignes à vérifier</x-slot>
                    @php($rows = $this->rows())
                    <div class="mb-4 max-w-xs">
                        <label for="student-import-row-filter" class="mb-1 block text-sm font-medium">Filtrer les lignes</label>
                        <select id="student-import-row-filter" wire:model.live="rowFilter" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900">
                            <option value="all">Toutes les lignes</option>
                            <option value="ready">Prêtes</option>
                            <option value="warnings">Avec avertissement</option>
                            <option value="errors">En erreur</option>
                            <option value="duplicates">Doublons à vérifier</option>
                        </select>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b text-left">
                                    <th class="p-2">Ligne</th>
                                    <th class="p-2">État</th>
                                    <th class="p-2">Problèmes / avertissements</th>
                                    <th class="p-2">Décision</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr class="border-b align-top">
                                        <td class="p-2">{{ $row->row_number }}</td>
                                        <td class="p-2">{{ match ($row->status) {
                                            'ready' => 'Prête',
                                            'error' => 'À corriger',
                                            'needs_review' => 'À vérifier',
                                            'created' => 'Créée',
                                            'updated' => 'Mise à jour',
                                            'ignored' => 'Ignorée',
                                            default => 'En attente',
                                        } }}</td>
                                        <td class="p-2">
                                            @foreach ($row->issues_json ?? [] as $issue)
                                                <div>{{ $issue['message'] }}</div>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="p-4 text-center text-sm text-gray-500">
                                                        Aucune ligne ne correspond à ce filtre.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </td>
                                        <td class="p-2">
                                            @if ($row->status === 'needs_review')
                                                <div class="flex flex-wrap gap-2">
                                                    @if (! collect($row->issues_json ?? [])->contains(fn ($issue) => ($issue['code'] ?? null) === 'duplicate_student_number'))
                                                        <x-filament::button size="xs" wire:click="resolveRow({{ $row->id }}, 'create')">Créer</x-filament::button>
                                                    @endif
                                                    @if ($row->candidate_student_id)
                                                        <span class="self-center text-xs text-gray-600">{{ $row->candidateStudent?->full_name }}</span>
                                                        <x-filament::button size="xs" color="warning" wire:click="resolveRow({{ $row->id }}, 'update')">Mettre à jour</x-filament::button>
                                                    @endif
                                                    <x-filament::button size="xs" color="gray" wire:click="resolveRow({{ $row->id }}, 'ignore')">Ignorer</x-filament::button>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $rows->links() }}</div>
                </x-filament::section>
            @endif
        @endif
    </div>
</x-filament-panels::page>
