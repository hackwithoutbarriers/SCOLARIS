<x-filament-panels::page>
    <div class="space-y-6">
        <div class="premium-card flex flex-wrap items-end justify-between gap-4 rounded-2xl bg-white p-5">
            <div><p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-600">Finance</p><h2 class="mt-2 text-xl font-extrabold text-primary-700">Journal de caisse</h2><p class="mt-1 text-sm text-gray-600">Recettes cash et dépenses de la journée.</p></div>
            <label class="text-sm font-semibold">Période<input type="date" wire:model.live="date" class="fi-input mt-1 block rounded-lg"></label>
        </div>
        @php($summary = $this->getSummary())
        <div class="grid gap-4 sm:grid-cols-4">
            @foreach ([['Solde d’ouverture', $summary['opening'], 'bg-primary-50'], ['Recettes cash', $summary['receipts'], 'bg-emerald-50'], ['Dépenses', $summary['expenses'], 'bg-rose-50'], ['Solde de clôture théorique', $summary['closing'], 'bg-amber-50']] as [$label, $value, $color])
                <div class="rounded-2xl {{ $color }} p-5"><span class="block text-xs text-gray-600">{{ $label }}</span><strong class="mt-2 block text-xl text-primary-700">{{ number_format($value, 0, ',', ' ') }} FCFA</strong></div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
