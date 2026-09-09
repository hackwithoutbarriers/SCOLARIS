<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scolaris — Gestion scolaire</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-scolaris-background font-sans text-scolaris-text">
    <main class="mx-auto flex min-h-screen max-w-6xl items-center px-4 py-10">
        <section class="grid w-full gap-8 overflow-hidden rounded-xl bg-white p-6 shadow-sm md:grid-cols-2 md:p-12">
            <div class="flex flex-col justify-center">
                <p class="text-sm font-bold uppercase tracking-[0.2em] text-scolaris-primary">SCOLARIS</p>
                <h1 class="mt-4 text-4xl font-black tracking-tight text-scolaris-primary md:text-5xl">La gestion scolaire, simplement.</h1>
                <p class="mt-5 max-w-xl text-lg text-slate-600">Un espace de travail sécurisé pour piloter les élèves, les présences, les notes, les bulletins et le recouvrement au même endroit.</p>
                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ url('/login') }}" class="rounded-xl bg-scolaris-primary px-6 py-3 text-center font-bold text-white hover:opacity-90">Se connecter</a>
                    <a href="{{ route('registration-requests.create') }}" class="rounded-xl border border-scolaris-primary px-6 py-3 text-center font-bold text-scolaris-primary hover:bg-slate-50">Demander un accès</a>
                </div>
            </div>
            <div class="flex items-center rounded-xl bg-slate-50 p-6 md:p-8">
                <div>
                    <h2 class="text-xl font-bold">Les décisions au bon moment</h2>
                    <ul class="mt-5 space-y-4 text-slate-600">
                        <li><strong class="text-scolaris-primary">Directeur</strong> : voyez les absences, notes, bulletins et impayés à traiter.</li>
                        <li><strong class="text-scolaris-primary">Enseignant</strong> : prenez l’appel sur mobile et complétez vos évaluations.</li>
                        <li><strong class="text-scolaris-primary">Comptable</strong> : suivez les paiements, les soldes et les relances.</li>
                        <li><strong class="text-scolaris-primary">Démarrage guidé</strong> : configurez votre école étape par étape.</li>
                    </ul>
                </div>
            </div>
        </section>
        <section class="mt-8 grid gap-4 md:grid-cols-3">
            <article class="rounded-xl bg-white p-5 shadow-sm"><h2 class="font-bold text-scolaris-primary">1. Configurez</h2><p class="mt-2 text-sm text-slate-600">École, année, classes, utilisateurs puis élèves.</p></article>
            <article class="rounded-xl bg-white p-5 shadow-sm"><h2 class="font-bold text-scolaris-primary">2. Travaillez</h2><p class="mt-2 text-sm text-slate-600">Chaque rôle retrouve directement ses actions prioritaires.</p></article>
            <article class="rounded-xl bg-white p-5 shadow-sm"><h2 class="font-bold text-scolaris-primary">3. Suivez</h2><p class="mt-2 text-sm text-slate-600">Présences, résultats, bulletins et recouvrement restent traçables.</p></article>
        </section>
    </main>
</body>
</html>
