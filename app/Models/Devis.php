<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Devis extends Model
{
    protected $fillable = [
        'client_nom',
        'client_telephone',
        'client_ville',
        'client_pays',
        'description_chantier',
        'lignes_facturation',
        'montant_materiel',
        'montant_main_doeuvre',
        'montant_total',
        'acompte_requis_pourcentage',
        'statut',
    ];

    protected $casts = [
        'montant_materiel' => 'decimal:2',
        'montant_main_doeuvre' => 'decimal:2',
        'montant_total' => 'decimal:2',
        'acompte_requis_pourcentage' => 'integer',
        'lignes_facturation' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $devis): void {
            $reference = $devis->reference_publique ?: self::reservePublicReference();

            $devis->reference_publique = $reference;
            $devis->numero_devis ??= 'DEV-'.$reference;
        });

        static::created(function (self $devis): void {
            DB::table('devis_public_references')
                ->where('reference_publique', $devis->reference_publique)
                ->update(['devis_id' => $devis->getKey(), 'updated_at' => now()]);
        });

        static::saving(function (self $devis): void {
            $lignes = is_array($devis->lignes_facturation) ? $devis->lignes_facturation : [];
            $totalPrestations = collect($lignes)->filter(fn (mixed $ligne): bool => is_array($ligne))->sum(function (array $ligne): float {
                return (float) ($ligne['quantite'] ?? 0) * (float) ($ligne['prix_unitaire'] ?? 0);
            });

            $devis->montant_materiel = $totalPrestations;
            $devis->montant_total = $totalPrestations + (float) ($devis->montant_main_doeuvre ?? 0);
        });

        static::updated(function (self $devis): void {
            if ($devis->wasChanged('statut') && $devis->statut === 'livre') {
                $devis->sendReviewRequest();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'reference_publique';
    }

    public static function reservePublicReference(): string
    {
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $reference = (string) random_int(10000, 99999);

            try {
                DB::table('devis_public_references')->insert([
                    'reference_publique' => $reference,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $reference;
            } catch (QueryException $exception) {
                if (! in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                    throw $exception;
                }
            }
        }

        throw ValidationException::withMessages([
            'reference_publique' => 'Toutes les références publiques disponibles sont épuisées.',
        ]);
    }

    /**
     * @return array<int, array{designation: string, description?: string, quantite: float|int, prix_unitaire: float|int, total?: float|int}>
     */
    public function billingLines(): array
    {
        $lines = is_array($this->lignes_facturation)
            ? array_values(array_filter($this->lignes_facturation, fn (mixed $line): bool => is_array($line)))
            : [];

        if ($lines !== [] || (float) $this->montant_materiel <= 0) {
            return $lines;
        }

        return [[
            'designation' => 'Matériel / fournitures',
            'description' => 'Montant matériel du devis',
            'quantite' => 1,
            'prix_unitaire' => (float) $this->montant_materiel,
            'total' => (float) $this->montant_materiel,
        ]];
    }

    public function temoignages(): HasMany
    {
        return $this->hasMany(Temoignage::class);
    }

    public function sendReviewRequest(): void
    {
        if (! $this->client_telephone || ! $this->client_nom) {
            return;
        }

        $googleUrl = config('services.google.place_id')
            ? 'https://www.google.com/search?q='.urlencode('TRENOU '.($this->client_ville ?? 'Togo'))
            : 'https://www.google.com/maps';

        $internalUrl = route('reviews.share', ['client' => $this->client_nom, 'projet_ref' => $this->numero_devis], false);
        $message = sprintf(
            'Bonjour %s, merci pour votre confiance. Merci de laisser un avis Google : %s. Et pour partager un témoignage photo, remplissez ce formulaire : %s',
            $this->client_nom,
            $googleUrl,
            $internalUrl
        );

        $number = config('services.whatsapp.number', '22890585976');
        $waLink = 'https://wa.me/'.$number.'?text='.urlencode($message);

        logger()->info('Review request sent', [
            'devis_id' => $this->id,
            'client' => $this->client_nom,
            'project_ref' => $this->numero_devis,
            'google_url' => $googleUrl,
            'internal_url' => $internalUrl,
            'whatsapp_url' => $waLink,
        ]);
    }
}
