<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicDevis extends Model
{
    protected $table = 'public_devis';

    protected $fillable = [
        'numero_demande',
        'nom',
        'telephone',
        'ville',
        'pays',
        'description_besoin',
        'categorie',
        'sous_type',
        'dimensions',
        'finition',
        'vitrage',
        'options',
        'estimation_min',
        'estimation_max',
        'devise',
        'source',
        'statut',
    ];

    protected $casts = [
        'dimensions' => 'array',
        'options' => 'array',
        'estimation_min' => 'integer',
        'estimation_max' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $publicDevis) {
            $publicDevis->numero_demande = 'DEM-'.str_pad((string) ((int) self::query()->max('id') + 1), 5, '0', STR_PAD_LEFT);
        });
    }

    public function convertToDevis(): Devis
    {
        $devis = Devis::query()->create([
            'client_nom' => $this->nom,
            'client_telephone' => $this->telephone,
            'client_ville' => $this->ville,
            'client_pays' => $this->pays ?? 'Togo',
            'description_chantier' => $this->description_besoin,
            'montant_materiel' => 0,
            'montant_main_doeuvre' => 0,
            'acompte_requis_pourcentage' => 0,
            'statut' => 'brouillon',
        ]);

        $this->update(['statut' => 'convertie']);

        return $devis;
    }
}
