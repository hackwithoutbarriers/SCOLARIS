<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activer votre compte — Scolaris</title>
    @vite(['resources/css/app.css'])
</head>
<body class="premium-shell min-h-screen font-sans text-scolaris-text">
<main class="mx-auto max-w-lg px-4 py-8">
    <section class="premium-card rounded-[1.75rem] bg-white/90 p-6 sm:p-10">
        <span class="text-xs font-extrabold uppercase tracking-[0.2em] text-scolaris-accent">SCOLARIS</span>
        <h1 class="mt-3 text-3xl font-extrabold text-scolaris-primary">Activer votre compte</h1>
        @if ($errors->any()) <div class="mt-4 rounded-xl bg-red-50 p-3 text-red-800">{{ $errors->first() }}</div> @endif
        <form method="POST" class="mt-6 grid gap-4">
            @csrf
            <input type="password" name="password" placeholder="Mot de passe" required class="rounded-xl border p-3">
            <input type="password" name="password_confirmation" placeholder="Confirmer le mot de passe" required class="rounded-xl border p-3">
            <button class="premium-button rounded-xl bg-scolaris-primary p-3.5 font-bold text-white">Activer le compte</button>
        </form>
    </section>
</main>
</body>
</html>
