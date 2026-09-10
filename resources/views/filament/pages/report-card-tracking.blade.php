<x-filament-panels::page>
    @php($summary = $this->getSummary())
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([['Total', 'total'], ['Brouillons', 'draft'], ['En vérification', 'review'], ['Approuvés', 'approved'], ['Publiés', 'published']] as [$label, $key])
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                <div class="text-sm text-gray-500">{{ $label }}</div>
                <div class="mt-2 text-2xl font-bold text-primary-700">{{ $summary[$key] }}</div>
            </div>
        @endforeach
    </div>
    <div class="overflow-x-auto rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5">
        <table class="w-full text-left text-sm">
            <thead class="border-b bg-gray-50 text-xs uppercase text-gray-500">
                <tr><th class="px-4 py-3">Élève</th><th class="px-4 py-3">Période</th><th class="px-4 py-3">Version</th><th class="px-4 py-3">État</th><th class="px-4 py-3">Mise à jour</th><th class="px-4 py-3">Contacter</th></tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($this->getRows() as $row)
                    <tr><td class="px-4 py-3 font-medium">{{ $row->student->full_name }}</td><td class="px-4 py-3">{{ $row->term?->name ?: 'Annuel' }}</td><td class="px-4 py-3">{{ $row->version }}</td><td class="px-4 py-3">{{ $row->status }}</td><td class="px-4 py-3">{{ $row->updated_at?->format('d/m/Y H:i') }}</td><td class="px-4 py-3">@php($whatsappUrl = $this->whatsappUrl($row)) @php($emailUrl = $this->emailUrl($row)) @if($whatsappUrl)<a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="rounded-lg bg-green-50 px-2 py-1 text-xs font-semibold text-green-700">WhatsApp</a>@else<span title="Numéro WhatsApp absent ou bulletin non publié" class="cursor-not-allowed rounded-lg bg-gray-100 px-2 py-1 text-xs text-gray-400" aria-disabled="true">WhatsApp</span>@endif @if($emailUrl)<a href="{{ $emailUrl }}" class="rounded-lg bg-primary-50 px-2 py-1 text-xs font-semibold text-primary-700">Email</a>@else<span title="Email non configuré — voir Paramètres" class="cursor-not-allowed rounded-lg bg-gray-100 px-2 py-1 text-xs text-gray-400" aria-disabled="true">Email</span>@endif</td></tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Aucun bulletin ne correspond aux filtres.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
