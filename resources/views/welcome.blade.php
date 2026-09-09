<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scolaris — Gestion scolaire</title>
    @vite(['resources/css/app.css'])
</head>
<body class="premium-shell min-h-screen font-sans text-scolaris-text">
    <main class="mx-auto min-h-screen max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between text-sm text-slate-600">
            <a href="/" class="font-extrabold tracking-[0.22em] text-scolaris-primary">SCOLARIS</a>
            <div class="rounded-full border border-slate-200 bg-white/75 px-3 py-2 shadow-sm backdrop-blur">
            <span class="font-medium">Langue :</span> <a href="{{ url('/language/fr') }}" class="font-bold text-scolaris-primary">Français</a>
            <span class="ml-2 text-slate-400" title="La traduction anglaise sera activée ultérieurement">Anglais (bientôt disponible)</span>
            </div>
        </div>
        <section class="fade-up mt-8 grid overflow-hidden rounded-[2rem] border border-white/70 bg-white/80 shadow-[0_30px_90px_rgba(23,59,103,.14)] backdrop-blur-xl md:grid-cols-[1.1fr_.9fr]">
            <div class="relative overflow-hidden p-8 sm:p-12 lg:p-16">
                <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-scolaris-accent/20 blur-3xl"></div>
                <div class="relative">
                    <span class="inline-flex items-center gap-2 rounded-full border border-scolaris-accent/30 bg-scolaris-accent/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-scolaris-primary">Pilotage scolaire</span>
                    <h1 class="mt-7 max-w-2xl text-4xl font-extrabold leading-tight text-scolaris-primary sm:text-5xl lg:text-6xl">L’excellence scolaire, avec une vision claire.</h1>
                    <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">Un espace de travail sécurisé pour décider plus vite, accompagner chaque élève et garder toute votre équipe alignée.</p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ url('/login') }}" class="premium-button rounded-xl bg-scolaris-primary px-6 py-3.5 text-center font-bold text-white hover:bg-[#102d50]">Se connecter <span aria-hidden="true">→</span></a>
                        <a href="{{ route('registration-requests.create') }}" class="rounded-xl border border-slate-200 bg-white px-6 py-3.5 text-center font-bold text-scolaris-primary hover:border-scolaris-primary hover:bg-scolaris-primary-light">Demander un accès</a>
                    </div>
                </div>
            </div>
            <div class="relative flex items-center bg-scolaris-primary p-8 text-white sm:p-12">
                <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 18px 18px;"></div>
                <div class="relative w-full">
                    <p class="text-sm font-bold uppercase tracking-[0.18em] text-scolaris-accent">Une même exigence</p>
                    <h2 class="mt-3 text-2xl font-extrabold sm:text-3xl">Les décisions au bon moment.</h2>
                    <div class="mt-8 space-y-3">
                        @foreach (['Directeur' => 'Une vue claire des priorités de l’établissement.', 'Enseignant' => 'Des parcours rapides pour les classes et les évaluations.', 'Comptable' => 'Un suivi précis des paiements et du recouvrement.'] as $role => $benefit)
                            <div class="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm"><strong class="block text-scolaris-accent">{{ $role }}</strong><span class="mt-1 block text-sm leading-6 text-white/75">{{ $benefit }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
        <section class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach ([['01', 'Configurez', 'Démarrage guidé : école, année, classes, utilisateurs puis élèves.'], ['02', 'Travaillez', 'Chaque rôle retrouve directement ses actions prioritaires.'], ['03', 'Suivez', 'Présences, résultats, bulletins et recouvrement restent traçables.']] as $item)
                <article class="premium-card fade-up rounded-2xl bg-white/85 p-6" style="animation-delay: {{ $loop->index * 100 }}ms"><span class="text-sm font-extrabold text-scolaris-accent">{{ $item[0] }}</span><h2 class="mt-3 text-xl font-extrabold text-scolaris-primary">{{ $item[1] }}</h2><p class="mt-2 leading-7 text-slate-600">{{ $item[2] }}</p></article>
            @endforeach
        </section>
    </main>
</body>
</html>
