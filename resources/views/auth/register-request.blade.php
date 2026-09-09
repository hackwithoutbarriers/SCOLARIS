<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demander un accès — Scolaris</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-scolaris-background font-sans text-scolaris-text">
<main class="mx-auto max-w-xl px-4 py-8">
    <section class="rounded-xl bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-bold text-scolaris-primary">Demander un accès Scolaris</h1>
        <p class="mt-2 text-sm text-slate-600">Les directeurs sont validés par le SaaS owner. Les autres rôles sont validés par le directeur de leur école.</p>
        @if (session('status')) <div class="mt-4 rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="mt-4 rounded-xl bg-red-50 p-3 text-red-800"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        <form method="POST" action="{{ route('registration-requests.store') }}" class="mt-6 grid gap-4">
            @csrf
            <input name="name" value="{{ old('name') }}" placeholder="Nom complet" required class="rounded-xl border p-3">
            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email professionnel" required class="rounded-xl border p-3">
            <input name="phone" value="{{ old('phone') }}" placeholder="Téléphone (optionnel)" class="rounded-xl border p-3">
            <select name="requested_role" required class="rounded-xl border p-3"><option value="">Rôle demandé</option><option value="director">Directeur</option><option value="teacher">Enseignant</option><option value="accountant">Comptable</option></select>
            <select name="school_id" class="rounded-xl border p-3"><option value="">École (obligatoire hors directeur)</option>@foreach (\App\Models\School::query()->where('active', true)->orderBy('name')->get() as $school)<option value="{{ $school->id }}" @selected(old('school_id') == $school->id)>{{ $school->name }}</option>@endforeach</select>
            <input name="school_name" value="{{ old('school_name') }}" placeholder="Nom de l'école (obligatoire pour un directeur)" class="rounded-xl border p-3">
            <input name="school_code" value="{{ old('school_code') }}" placeholder="Code école (optionnel)" class="rounded-xl border p-3">
            <input type="password" name="password" placeholder="Mot de passe fort" required class="rounded-xl border p-3">
            <input type="password" name="password_confirmation" placeholder="Confirmer le mot de passe" required class="rounded-xl border p-3">
            <button class="rounded-xl bg-scolaris-primary p-3 font-bold text-white">Envoyer la demande</button>
        </form>
        <a class="mt-4 inline-block text-sm text-scolaris-primary" href="{{ url('/admin/login') }}">Retour à la connexion</a>
    </section>
</main>
</body>
</html>
