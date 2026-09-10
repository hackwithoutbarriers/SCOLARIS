<x-filament-panels::page>
    @php
        $statusLabels = ['OVERDUE' => 'En retard', 'PARTIALLY_PAID' => 'Partiellement payée', 'ISSUED' => 'Émise', 'PAID' => 'Payée'];
        $statusClasses = ['OVERDUE' => 'scolaris-status--danger', 'PARTIALLY_PAID' => 'scolaris-status--warning', 'ISSUED' => 'scolaris-status--info', 'PAID' => 'scolaris-status--success'];
    @endphp
    <div class="space-y-6">
        <div class="premium-card rounded-2xl bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div><p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-600">Finance</p><h2 class="mt-2 text-xl font-extrabold text-primary-700">Centre de recouvrement</h2><p class="mt-1 text-sm text-gray-600">Priorisez les soldes ouverts et les factures en retard.</p></div>
                <div class="grid grid-cols-2 gap-3 sm:flex"><div class="rounded-xl bg-primary-50 px-4 py-3"><span class="block text-xs text-gray-500">À recouvrer</span><strong class="text-primary-700">{{ number_format($this->getTotalOutstanding(), 0, ',', ' ') }} FCFA</strong></div><select wire:model.live="status" class="fi-input rounded-lg"><option value="all">Tous les statuts</option><option value="OVERDUE">En retard</option><option value="PARTIALLY_PAID">Partiels</option><option value="ISSUED">Émises</option></select></div>
            </div>
        </div>
        <div class="premium-card overflow-x-auto rounded-2xl bg-white"><table class="w-full text-left text-sm"><thead class="bg-primary-50 text-primary-700"><tr><th class="p-4">Élève</th><th class="p-4">Échéance</th><th class="p-4">Statut</th><th class="p-4 text-right">Solde</th><th class="p-4"></th></tr></thead><tbody>
            @forelse ($this->getInvoices() as $invoice)
                <tr class="border-t border-gray-100"><td class="p-4 font-semibold">{{ $invoice->student?->full_name }}</td><td class="p-4">{{ $invoice->due_date?->format('d/m/Y') ?? '—' }}</td><td class="p-4"><span class="scolaris-status {{ $statusClasses[$invoice->status] ?? 'scolaris-status--info' }}">{{ $statusLabels[$invoice->status] ?? $invoice->status }}</span></td><td class="p-4 text-right font-bold tabular-nums text-primary-700">{{ number_format($invoice->balance(), 0, ',', ' ') }} FCFA</td><td class="p-4 text-right"><div class="flex flex-wrap justify-end gap-2"><a class="font-semibold text-primary-700 hover:underline" href="{{ \App\Filament\Resources\PaymentResource::getUrl('create') }}">Enregistrer un paiement</a>@php($whatsappUrl = $this->whatsappUrl($invoice))@php($emailUrl = $this->emailUrl($invoice))@if($whatsappUrl)<a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="rounded-lg bg-green-50 px-2 py-1 text-xs font-semibold text-green-700">WhatsApp</a>@else<span title="Numéro WhatsApp absent ou invalide" class="cursor-not-allowed rounded-lg bg-gray-100 px-2 py-1 text-xs text-gray-400" aria-disabled="true">WhatsApp</span>@endif @if($emailUrl)<a href="{{ $emailUrl }}" class="rounded-lg bg-primary-50 px-2 py-1 text-xs font-semibold text-primary-700">Email</a>@else<span title="Email non configuré — voir Paramètres" class="cursor-not-allowed rounded-lg bg-gray-100 px-2 py-1 text-xs text-gray-400" aria-disabled="true">Email</span>@endif</div></td></tr>
            @empty
                <tr><td colspan="5" class="p-8 text-center text-gray-500">Aucun débiteur correspondant aux filtres.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
</x-filament-panels::page>
