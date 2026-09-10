<x-filament-panels::page>
    <div class="space-y-6">
        <div class="premium-card rounded-2xl bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div><p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-600">SaaS · Support</p><h2 class="mt-2 text-xl font-extrabold text-primary-700">Intervenir pour une école</h2><p class="mt-1 text-sm text-gray-600">Sélectionnez un établissement, consultez son état et ouvrez les outils administratifs adaptés.</p></div>
                <select wire:model.live="school" class="fi-input w-full rounded-lg lg:max-w-sm"><option value="">Sélectionner une école</option>@foreach ($this->getSchools() as $schoolOption)<option value="{{ $schoolOption->id }}">{{ $schoolOption->name }}</option>@endforeach</select>
            </div>
        </div>
        @if ($selectedSchool = $this->getSelectedSchool())
            @php($metrics = $this->getMetrics())
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([['Utilisateurs', $metrics['users']], ['Élèves', $metrics['students']], ['Classes', $metrics['classes']], ['Impayés', $metrics['overdue']]] as [$label, $value])
                    <div class="premium-card rounded-2xl bg-white p-5"><div class="text-sm text-gray-500">{{ $label }}</div><div class="mt-2 text-2xl font-extrabold text-primary-700">{{ $value }}</div></div>
                @endforeach
            </div>
            <div class="premium-card rounded-2xl bg-white p-5 sm:p-6">
                <h3 class="font-extrabold text-primary-700">{{ $selectedSchool->name }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ $selectedSchool->code }} · {{ $selectedSchool->city ?: 'Ville non renseignée' }}</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <button wire:click="recordIntervention('school_configuration_opened', '{{ \App\Filament\Resources\SchoolResource::getUrl('edit', ['record' => $selectedSchool]) }}')" class="fi-btn fi-color-primary cursor-pointer">Configurer l'école</button>
                    <button wire:click="recordIntervention('users_management_opened', '{{ \App\Filament\Resources\UserResource::getUrl() }}')" class="fi-btn cursor-pointer">Gérer les utilisateurs</button>
                </div>
            </div>
            <div class="premium-card rounded-2xl bg-white p-5 sm:p-6">
                <h3 class="font-extrabold text-primary-700">Journal des interventions</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($this->getInterventions() as $intervention)
                        <div class="flex flex-col gap-1 border-b border-gray-100 pb-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                            <span>{{ str_replace('_', ' ', str_replace('super_admin.', '', $intervention->action)) }}</span>
                            <span class="text-gray-500">{{ $intervention->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Aucune intervention enregistrée.</p>
                    @endforelse
                </div>
            </div>
        @else
            <div class="premium-card rounded-2xl bg-white p-8 text-center"><p class="font-semibold text-primary-700">Aucune école sélectionnée</p><p class="mt-1 text-sm text-gray-500">Choisissez une école pour commencer l'assistance.</p></div>
        @endif
    </div>
</x-filament-panels::page>
