<?php

use App\Http\Controllers\PdfController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/services', [PublicController::class, 'services'])->name('services');
Route::get('/services/{slug}', [PublicController::class, 'service'])->where('slug', '[a-z0-9-]+')->name('services.detail');
Route::get('/galerie', [PublicController::class, 'gallery'])->name('gallery');
Route::get('/avis', [ReviewController::class, 'index'])->name('reviews');
Route::get('/avis/partager', [ReviewController::class, 'shareForm'])->name('reviews.share');
Route::post('/avis/partager', [ReviewController::class, 'storeShare'])->middleware('throttle:10,1')->name('reviews.store');
Route::get('/devis', [PublicController::class, 'showQuoteForm'])->name('public.devis');
Route::post('/devis', [PublicController::class, 'storeQuote'])->middleware('throttle:10,1')->name('public.devis.store');
Route::post('/api/devis', [PublicController::class, 'storeQuoteApi'])->middleware('throttle:10,1')->name('api.devis.store');
Route::get('/api/reviews', [ReviewController::class, 'api'])->name('api.reviews');
Route::get('/contact', [PublicController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicController::class, 'storeContact'])->middleware('throttle:10,1')->name('contact.store');

Route::middleware('auth')->group(function (): void {
    Route::get('/devis/{devis}/pdf', [PdfController::class, 'devis'])->name('devis.pdf');
    Route::get('/attestations/{attestation}/certificat.pdf', [PdfController::class, 'certificat'])->name('certificat.pdf');
    Route::get('/attestations/{attestation}/pdf', [PdfController::class, 'attestation'])->name('attestation.pdf');
    Route::get('/attestations/{attestation}/documents', [PdfController::class, 'documentLinks'])->name('documents.links');
});
