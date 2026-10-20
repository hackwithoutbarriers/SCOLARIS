<x-filament-panels::page>
    @php($steps = $this->getSteps())
    <div class="space-y-6 premium-shell -m-4 p-4 lg:-m-8 lg:p-8">
        <div class="premium-card rounded-2xl bg-white/90 p-6 backdrop-blur dark:bg-gray-900/90">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold">Mise en route de votre école</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Configurez les éléments essentiels, dans l’ordre qui vous convient.</p>
                </div>
                <div class="text-right"><strong class="text-2xl text-primary-600">{{ $this->progress }} %</strong><div class="text-xs text-gray-500">progression</div></div>
            </div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700"><div class="h-full rounded-full bg-primary-600" style="width: {{ $this->progress }}%"></div></div>
        </div>
        @php($nextStep = collect($steps)->first(fn ($step) => ! $step['complete']))
        @if ($nextStep)
            <div class="premium-card flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-primary-200 bg-primary-50 p-5 dark:border-primary-900 dark:bg-primary-950/30">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Prochaine action recommandée</p>
                    <h3 class="mt-1 font-semibold">{{ $nextStep['title'] }}</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $nextStep['description'] }}</p>
                </div>
                <x-filament::button tag="a" :href="$nextStep['url']">Continuer cette étape</x-filament::button>
            </div>
        @else
            <div class="premium-card rounded-2xl border border-success-200 bg-success-50 p-5 dark:border-success-900 dark:bg-success-950/30">
                <h3 class="font-semibold text-success-800 dark:text-success-200">Les éléments de démarrage sont configurés</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Les cinq étapes contiennent des données. Vous pouvez poursuivre les opérations quotidiennes depuis le tableau de bord.</p>
            </div>
        @endif
        <div class="grid gap-4 md:grid-cols-5">
            @foreach ($steps as $index => $step)
                <a href="{{ $step['url'] }}" class="premium-card rounded-2xl border p-4 transition hover:border-primary-500 {{ auth()->user()->onboarding_step === $index + 1 ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/30' : 'bg-white dark:bg-gray-900' }}">
                    <div class="flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-full {{ $step['complete'] ? 'bg-success-100 text-success-700' : 'bg-gray-100 text-gray-600' }}">{{ $step['complete'] ? '✓' : $index + 1 }}</span><strong class="text-sm">{{ $step['title'] }}</strong></div>
                    <p class="mt-3 text-xs text-gray-600 dark:text-gray-300">{{ $step['description'] }}</p>
                </a>
            @endforeach
        </div>
        <div class="flex justify-between gap-3">
            <x-filament::button color="gray" wire:click="back" :disabled="auth()->user()->onboarding_step <= 1">Étape précédente</x-filament::button>
            <x-filament::button wire:click="next">Étape suivante</x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
