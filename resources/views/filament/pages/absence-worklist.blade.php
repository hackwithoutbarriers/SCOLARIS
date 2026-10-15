<x-filament-panels::page>
    @php($summary = $this->summary())
    <div class="absence-worklist space-y-6">
        <div class="premium-card rounded-2xl bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-amber-600">Suivi quotidien</p>
                    <h2 class="mt-2 text-xl font-extrabold text-primary-700">
                        Absences du {{ \Carbon\Carbon::parse($date)->locale('fr')->translatedFormat('d F Y') }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">Contactez les familles et conservez la trace de chaque échange.</p>
                </div>
                <label class="flex w-full flex-col gap-1 text-sm font-semibold text-gray-700 lg:w-auto">
                    <span>Date à consulter</span>
                    <input type="date" wire:model.live="date" class="fi-input w-full rounded-lg lg:w-auto">
                </label>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'À traiter', 'value' => $summary['total'], 'class' => 'bg-primary-50 text-primary-700'],
                ['label' => 'À contacter', 'value' => $summary['to_contact'], 'class' => 'bg-amber-50 text-amber-700'],
                ['label' => 'Justifiées', 'value' => $summary['justified'], 'class' => 'bg-green-50 text-green-700'],
                ['label' => 'Non justifiées', 'value' => $summary['unjustified'], 'class' => 'bg-red-50 text-red-700'],
            ] as $item)
                <div class="rounded-xl px-4 py-3 {{ $item['class'] }}">
                    <p class="text-sm font-semibold">{{ $item['label'] }}</p>
                    <p class="mt-1 text-2xl font-extrabold">{{ $item['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="space-y-4">
            @forelse ($this->getAbsences() as $absence)
                <article class="premium-card rounded-2xl bg-white p-4 sm:p-5">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-primary-700">{{ $absence->student?->full_name }}</h3>
                                <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                    {{ $absence->status === 'LATE' ? 'Retard' : 'Absence' }}
                                </span>
                                @if ($absence->followUp)
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                        {{ \App\Models\AbsenceFollowUp::STATUSES[$absence->followUp->status] ?? 'À contacter' }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ $absence->session?->classRoom?->name ?: 'Classe non renseignée' }}
                                · {{ $absence->session?->teacher?->name ?: 'Enseignant non renseigné' }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2 xl:justify-end">
                            @php($whatsappUrl = $this->whatsappUrl($absence))
                            @php($emailUrl = $this->emailUrl($absence))
                            @if ($whatsappUrl)
                                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="rounded-lg bg-green-50 px-3 py-2 text-xs font-semibold text-green-700">Ouvrir WhatsApp</a>
                            @else
                                <span class="rounded-lg bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-400">WhatsApp indisponible</span>
                            @endif
                            @if ($emailUrl)
                                <a href="{{ $emailUrl }}" class="rounded-lg bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700">Préparer l’e-mail</a>
                            @else
                                <span class="rounded-lg bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-400">E-mail indisponible</span>
                            @endif
                        </div>
                    </div>

                    @if (auth()->user()?->isSecretary())
                        <div class="mt-4 grid gap-3 border-t border-gray-100 pt-4 md:grid-cols-2 xl:grid-cols-[1fr_1fr_2fr_auto] xl:items-end">
                            <label class="text-sm font-semibold text-gray-700">
                                <span class="mb-1 block">Résultat du suivi</span>
                                <select wire:model="followUpStatus.{{ $absence->id }}" class="fi-input w-full rounded-lg text-sm">
                                    @foreach ($this->followUpStatuses() as $status => $label)
                                        <option value="{{ $status }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-sm font-semibold text-gray-700">
                                <span class="mb-1 block">Canal utilisé</span>
                                <input wire:model="followUpChannel.{{ $absence->id }}" placeholder="WhatsApp, téléphone..." class="fi-input w-full rounded-lg text-sm">
                            </label>
                            <label class="text-sm font-semibold text-gray-700">
                                <span class="mb-1 block">Remarque ou justification</span>
                                <input wire:model="followUpNotes.{{ $absence->id }}" placeholder="Saisir une note utile à l’équipe" class="fi-input w-full rounded-lg text-sm">
                            </label>
                            <button type="button" wire:click="saveFollowUp({{ $absence->id }})" class="rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-700">
                                Enregistrer le suivi
                            </button>
                        </div>
                    @endif
                </article>
            @empty
                <div class="premium-card rounded-2xl bg-white p-8 text-center">
                    <p class="font-semibold text-primary-700">Aucune absence à traiter</p>
                    <p class="mt-1 text-sm text-gray-500">Les absences de cette date sont suivies ou aucune présence n’a encore été enregistrée.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
