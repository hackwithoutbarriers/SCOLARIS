<div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <label class="text-sm">
                <span class="mb-1 block font-medium">Date de présence</span>
                <input type="date" wire:model.live="date" class="fi-input rounded-lg border-gray-300">
            </label>
            <label class="text-sm">
                <span class="mb-1 block font-medium">Période de supervision</span>
                <select wire:model.live="period" class="fi-input rounded-lg border-gray-300">
                    @foreach ($this->getPeriodOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm">
                <span class="mb-1 block font-medium">Classe</span>
                <select wire:model.live="classRoomId" class="fi-input rounded-lg border-gray-300">
                    <option value="">Toutes les classes</option>
                    @foreach ($this->getClassOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        @php($stats = $this->getStats())
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div><div class="text-sm text-gray-500">Taux de présence</div><div class="text-2xl font-semibold">{{ $stats['rate'] }}</div></div>
            <div><div class="text-sm text-gray-500">Absents aujourd’hui</div><div class="text-2xl font-semibold">{{ $stats['absent'] }}</div></div>
            <div><div class="text-sm text-gray-500">Retards aujourd’hui</div><div class="text-2xl font-semibold">{{ $stats['late'] }}</div></div>
            <div><div class="text-sm text-gray-500">Appels non validés</div><div class="text-2xl font-semibold">{{ $stats['unvalidated'] }}</div></div>
        </div>
        @php($supervision = $this->getSupervision())
        <div class="mt-6 border-t border-gray-100 pt-5">
            <h3 class="text-base font-bold text-primary-700">Supervision des absences</h3>
            <div class="mt-3 grid grid-cols-3 gap-4">
                <div><div class="text-sm text-gray-500">À contacter</div><div class="text-2xl font-semibold text-amber-700">{{ $supervision['to_contact'] }}</div></div>
                <div><div class="text-sm text-gray-500">Non justifiées</div><div class="text-2xl font-semibold text-red-700">{{ $supervision['unjustified'] }}</div></div>
                <div><div class="text-sm text-gray-500">À surveiller</div><div class="text-2xl font-semibold text-blue-700">{{ $supervision['to_monitor'] }}</div></div>
            </div>
            <div class="mt-5 grid gap-5 lg:grid-cols-2">
                <div>
                    <h4 class="text-sm font-semibold text-gray-700">Élèves récurrents (3 absences ou retards)</h4>
                    <div class="mt-2 space-y-2">
                        @forelse ($supervision['recurrent'] as $item)
                            <div class="flex justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm">
                                <span>{{ $item->student?->full_name }} <span class="text-gray-500">· {{ $item->student?->currentEnrollment?->classRoom?->name }}</span></span>
                                <strong>{{ $item->absence_count }}</strong>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Aucun élève à surveiller sur cette période.</p>
                        @endforelse
                    </div>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-gray-700">Classes les plus concernées</h4>
                    <div class="mt-2 space-y-2">
                        @forelse ($supervision['classes'] as $item)
                            <div class="flex justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm"><span>{{ $item->name }}</span><strong>{{ $item->absence_count }}</strong></div>
                        @empty
                            <p class="text-sm text-gray-500">Aucune donnée pour cette période.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
</div>
