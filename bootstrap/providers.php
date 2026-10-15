<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    \App\Providers\Filament\AdminPanelProvider::class,
    \Barryvdh\DomPDF\ServiceProvider::class,
];
