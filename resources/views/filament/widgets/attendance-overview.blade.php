<div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="mb-4 flex flex-wrap items-end gap-3">
            <label class="text-sm">
                <span class="mb-1 block font-medium">Date</span>
                <input type="date" wire:model.live="date" class="fi-input rounded-lg border-gray-300">
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
</div>
