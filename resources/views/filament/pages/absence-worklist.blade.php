<x-filament-panels::page>
    <div class="space-y-6">
        <div class="premium-card rounded-2xl bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-600">À traiter</p>
                    <h2 class="mt-2 text-xl font-extrabold text-primary-700">Absences du {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</h2>
                    <p class="mt-1 text-sm text-gray-600">Identifiez les absences qui nécessitent une justification ou un suivi.</p>
                </div>
                <input type="date" wire:model.live="date" class="fi-input w-full rounded-lg sm:w-auto">
            </div>
        </div>
        <div class="premium-card overflow-hidden rounded-2xl bg-white">
            @forelse ($this->getAbsences() as $absence)
                <div class="flex flex-col gap-2 border-b border-gray-100 p-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
                    <div><p class="font-semibold text-primary-700">{{ $absence->student?->full_name }}</p><p class="text-sm text-gray-500">{{ $absence->session?->classRoom?->name }} · {{ $absence->session?->teacher?->name }}</p></div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">{{ $absence->status === 'LATE' ? 'Retard' : 'À justifier' }}</span>
                        @php($whatsappUrl = $this->whatsappUrl($absence))
                        @php($emailUrl = $this->emailUrl($absence))
                        @if ($whatsappUrl)
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="rounded-lg bg-green-50 px-3 py-2 text-xs font-semibold text-green-700">WhatsApp</a>
                        @else
                            <span title="Numéro WhatsApp absent ou invalide" class="cursor-not-allowed rounded-lg bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-400" aria-disabled="true">WhatsApp</span>
                        @endif
                        @if ($emailUrl)
                            <a href="{{ $emailUrl }}" class="rounded-lg bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700">Email</a>
                        @else
                            <span title="Email non configuré — voir Paramètres" class="cursor-not-allowed rounded-lg bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-400" aria-disabled="true">Email</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center"><p class="font-semibold text-primary-700">Aucune absence à traiter</p><p class="mt-1 text-sm text-gray-500">Les absences de cette date sont suivies ou aucune présence n'a encore été enregistrée.</p></div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
