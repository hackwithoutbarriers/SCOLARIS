<x-filament-panels::page>
    <div class="space-y-6 premium-shell -m-4 p-4 lg:-m-8 lg:p-8">
        <div class="premium-card rounded-2xl bg-white/90 p-4 backdrop-blur dark:bg-gray-900/90 sm:p-6">
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">Le fichier est validé avant insertion. Une ligne invalide est ignorée et détaillée ci-dessous.</p>
            {{ $this->form }}
            @if ($preview)
                <div class="mt-5 rounded-xl border border-primary-200 bg-primary-50 p-4 text-sm dark:bg-primary-950/30">
                    <strong>Prévisualisation</strong>
                    <p class="mt-1">{{ $preview['analyzed'] }} ligne(s), {{ $preview['valid'] }} valide(s), {{ $preview['invalid'] }} invalide(s).</p>
                    <p class="mt-1">Colonnes reconnues : {{ implode(', ', $preview['headers']) ?: 'aucune' }}</p>
                    @if ($preview['unknown']) <p class="mt-1 text-warning-700">Colonnes inconnues : {{ implode(', ', $preview['unknown']) }}</p> @endif
                    @if ($preview['missing']) <p class="mt-1 text-danger-700">Colonnes manquantes : {{ implode(', ', $preview['missing']) }}</p> @endif
                    @foreach (array_slice($preview['errors'], 0, 10) as $error)
                        <div class="mt-1">Ligne {{ $error['line'] }} : {{ $error['message'] }}</div>
                    @endforeach
                </div>
            @endif
        </div>
        @if ($result)
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-6">
                @foreach (['analyzed' => 'Analysées', 'created' => 'Importées', 'updated' => 'Mises à jour', 'ignored' => 'Ignorées', 'duplicates' => 'Doublons', 'errors' => 'Erreurs'] as $key => $label)
                    <div class="premium-card rounded-2xl bg-white/90 p-4 backdrop-blur dark:bg-gray-900/90">
                        <div class="text-xs text-gray-500">{{ $label }}</div>
                        <div class="text-2xl font-bold text-[#0F172A] dark:text-white">{{ $result[$key] ?? 0 }}</div>
                    </div>
                @endforeach
            </div>
            @if (!empty($result['details']))
                <div class="premium-card overflow-x-auto rounded-2xl bg-white/90 p-4 text-sm backdrop-blur dark:bg-gray-900/90">
                    @foreach ($result['details'] as $detail)
                        <div class="border-b py-2 last:border-0">Ligne {{ $detail['line'] }} : {{ $detail['message'] }}</div>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
