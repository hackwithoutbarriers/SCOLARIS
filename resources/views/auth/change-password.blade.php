<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Changer le mot de passe — Scolaris</title>
    @vite(['resources/css/app.css'])
</head>
<body class="premium-shell min-h-screen font-sans text-scolaris-text">
<main class="mx-auto max-w-lg px-4 py-8">
    <section class="premium-card rounded-[1.75rem] bg-white/90 p-6 sm:p-10">
        <h1 class="text-3xl font-extrabold text-scolaris-primary">Changement obligatoire</h1>
        <p class="mt-2 text-sm text-slate-600">Choisissez un nouveau mot de passe avant de continuer.</p>
        @if ($errors->any()) <div class="mt-4 rounded-xl bg-red-50 p-3 text-red-800">{{ $errors->first() }}</div> @endif
        <form method="POST" class="mt-6 grid gap-4">
            @csrf
            <input type="password" name="password" placeholder="Nouveau mot de passe" required class="rounded-xl border p-3">
            <input type="password" name="password_confirmation" placeholder="Confirmer le mot de passe" required class="rounded-xl border p-3">
            <button class="premium-button rounded-xl bg-scolaris-primary p-3.5 font-bold text-white">Enregistrer</button>
        </form>
    </section>
</main>
</body>
</html>
