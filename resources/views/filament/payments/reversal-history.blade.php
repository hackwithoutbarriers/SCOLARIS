<div class="space-y-3">
    @forelse ($reversals as $reversal)
        <div class="rounded-lg border border-gray-200 p-3 text-sm">
            <div class="flex items-center justify-between gap-3">
                <span class="font-semibold">{{ number_format($reversal->amount, 0, ',', ' ') }} XOF</span>
                <span class="text-gray-500">{{ $reversal->created_at?->format('d/m/Y H:i') }}</span>
            </div>
            <p class="mt-1 text-gray-700">{{ $reversal->reason }}</p>
            <p class="mt-1 text-xs text-gray-500">Par {{ $reversal->creator?->name ?? 'Utilisateur supprimé' }}</p>
        </div>
    @empty
        <p class="text-sm text-gray-600">Aucune correction enregistrée pour ce paiement.</p>
    @endforelse
</div>
