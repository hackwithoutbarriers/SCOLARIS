@php
    $user = auth()->user();
    $roleLabels = [
        'super_admin' => 'Super administrateur',
        'director' => 'Directeur',
        'admin' => 'Administrateur',
        'principal' => 'Principal',
        'teacher' => 'Enseignant',
        'accountant' => 'Comptable',
        'secretary' => 'Secrétaire',
    ];
@endphp

<div class="scolaris-context-bar" role="status" aria-label="Contexte de session">
    <div class="scolaris-context-bar__school">
        <span class="scolaris-context-bar__label">Établissement</span>
        <strong>{{ $user?->school?->name ?? 'Administration Scolaris' }}</strong>
    </div>
    @if ($user)
        @php
            $roleAccent = match ($user->role) {
                'director', 'admin', 'principal' => '#173B67',
                'accountant' => '#168A68',
                'teacher' => '#D6A84F',
                default => '#64748B',
            };
        @endphp
        <span class="scolaris-role-chip" style="--role-accent: {{ $roleAccent }}">
            <span class="scolaris-role-chip__dot" aria-hidden="true"></span>
            {{ $roleLabels[$user->role] ?? $user->role }}
        </span>
    @endif
</div>
