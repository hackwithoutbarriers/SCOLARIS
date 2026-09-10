<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Supervision des écoles</x-slot>
        <x-slot name="description">Sélectionnez un établissement pour consulter sa configuration et intervenir avec votre périmètre administrateur.</x-slot>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[40rem] text-left text-sm">
                <thead class="text-gray-500">
                    <tr>
                        <th class="px-3 py-2 font-medium">École</th>
                        <th class="px-3 py-2 font-medium">Statut</th>
                        <th class="px-3 py-2 font-medium">Utilisateurs</th>
                        <th class="px-3 py-2 font-medium">Élèves</th>
                        <th class="px-3 py-2 font-medium">Classes</th>
                        <th class="px-3 py-2 font-medium">Abonnement</th>
                        <th class="px-3 py-2 text-right font-medium">Assistance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->getSchools() as $school)
                        <tr class="border-t border-gray-100">
                            <td class="px-3 py-3">
                                <div class="font-semibold text-primary-700">{{ $school->name }}</div>
                                <div class="text-xs text-gray-500">{{ $school->code }}</div>
                            </td>
                            <td class="px-3 py-3">
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $school->active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">{{ $school->active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-3 py-3">{{ $school->users_count }}</td>
                            <td class="px-3 py-3">{{ $school->students_count }}</td>
                            <td class="px-3 py-3">{{ $school->class_rooms_count }}</td>
                            <td class="px-3 py-3">{{ $school->activeSubscription?->plan?->name ?: 'Non configuré' }}</td>
                            <td class="px-3 py-3 text-right">
                                <a class="font-semibold text-primary-700 hover:underline" href="{{ \App\Filament\Pages\SchoolSupport::getUrl(['school' => $school->id]) }}">Ouvrir l'assistance</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-8 text-center text-gray-500">Aucune école enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
