<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demander un accès — Scolaris</title>
    @vite(['resources/css/app.css'])
</head>
<body class="premium-shell min-h-screen font-sans text-scolaris-text">
<main class="mx-auto max-w-2xl px-4 py-8 sm:px-6">
    <div class="mb-4 text-right text-sm">
        <span class="font-medium">Langue :</span> <a href="{{ url('/language/fr') }}" class="font-bold text-scolaris-primary">Français</a>
        <span class="ml-2 text-slate-400">Anglais (bientôt disponible)</span>
    </div>
    <section class="premium-card rounded-[1.75rem] bg-white/90 p-6 backdrop-blur sm:p-10">
    <div class="mb-8"><span class="text-xs font-extrabold uppercase tracking-[0.2em] text-scolaris-accent">SCOLARIS</span><h1 class="mt-3 text-3xl font-extrabold text-scolaris-primary">Demander un accès</h1></div>
        <p class="mt-2 text-sm text-slate-600">Les directeurs sont validés par l’administrateur de la plateforme. Le personnel est invité directement par le responsable de son école.</p>
        @if (session('status')) <div class="mt-4 rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="mt-4 rounded-xl bg-red-50 p-3 text-red-800"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div> @endif
        <form method="POST" action="{{ route('registration-requests.store') }}" class="mt-6 grid gap-4">
            @csrf
            <fieldset class="grid gap-3">
                <legend class="font-bold text-scolaris-primary">Votre démarche</legend>
                <label class="flex items-start gap-3 rounded-xl border p-3"><input type="radio" name="request_type" value="new_school" @checked(old('request_type', 'new_school') === 'new_school')> <span><strong>Créer l’accès de mon école</strong><small class="block text-sm text-slate-600">Pour une école qui ne possède pas encore de compte Scolaris.</small></span></label>
                <label class="flex items-start gap-3 rounded-xl border p-3"><input type="radio" name="request_type" value="existing_school" @checked(old('request_type') === 'existing_school')> <span><strong>Rejoindre une école existante</strong><small class="block text-sm text-slate-600">Votre demande sera vérifiée par le responsable de l’école.</small></span></label>
            </fieldset>
            <input name="name" value="{{ old('name') }}" placeholder="Nom complet" required class="rounded-xl border p-3">
            <div class="grid gap-2 sm:grid-cols-2">
                <input type="email" name="email" value="{{ old('email') }}" placeholder="E-mail professionnel (optionnel si WhatsApp)" class="rounded-xl border p-3">
                <input name="phone" value="{{ old('phone') }}" placeholder="Téléphone / WhatsApp (optionnel si e-mail)" class="rounded-xl border p-3">
            </div>
            <p class="text-xs text-slate-600">Indiquez au moins un moyen de contact. Le canal disponible sera utilisé après validation.</p>
            <input type="hidden" name="requested_role" value="director">
            <input name="school_name" value="{{ old('school_name') }}" placeholder="Nom de l'école" required class="rounded-xl border p-3">
            <input name="school_code" value="{{ old('school_code') }}" placeholder="Code école (obligatoire pour une école existante)" class="rounded-xl border p-3">
            <input name="city" value="{{ old('city') }}" placeholder="Ville (facultatif)" class="rounded-xl border p-3">
            <textarea name="message" placeholder="Message ou précision (facultatif)" class="rounded-xl border p-3">{{ old('message') }}</textarea>
            <button class="premium-button rounded-xl bg-scolaris-primary p-3.5 font-bold text-white hover:bg-[#102d50]">Envoyer la demande <span aria-hidden="true">→</span></button>
        </form>
        <script>
            const typeInputs = document.querySelectorAll('input[name="request_type"]');
            const schoolCode = document.querySelector('input[name="school_code"]');
            const email = document.querySelector('input[name="email"]');
            const phone = document.querySelector('input[name="phone"]');
            const syncRequirements = () => {
                const existing = document.querySelector('input[name="request_type"]:checked')?.value === 'existing_school';
                schoolCode.required = existing;
                email.required = !phone.value.trim();
                phone.required = !email.value.trim();
            };
            typeInputs.forEach((input) => input.addEventListener('change', syncRequirements));
            [email, phone].forEach((input) => input.addEventListener('input', syncRequirements));
            syncRequirements();
        </script>
        <a class="mt-4 inline-block text-sm text-scolaris-primary" href="{{ url('/admin/login') }}">Retour à la connexion</a>
    </section>
</main>
</body>
</html>
