<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Attestation extends Model
{
    protected $fillable = [
        'apprenti_nom_prenom',
        'photo_profil',
        'date_naissance',
        'lieu_naissance',
        'nationalite',
        'type_document',
        'date_debut_apprentissage',
        'date_fin_apprentissage',
        'date_delivrance',
    ];

    protected $casts = [
        'date_debut_apprentissage' => 'date',
        'date_naissance' => 'date',
        'date_fin_apprentissage' => 'date',
        'date_delivrance' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $attestation) {
            $attestation->numero_attestation ??= 'ATT-TEMP-'.Str::uuid();
        });

        static::created(function (self $attestation): void {
            $attestation->numero_serie = (string) $attestation->id;
            $attestation->numero_attestation = $attestation->documentNumber();
            $attestation->saveQuietly();
        });
    }

    public function serialNumber(): string
    {
        if (filled($this->numero_serie)) {
            return (string) $this->numero_serie;
        }

        preg_match('/(\d+)$/', (string) $this->numero_attestation, $matches);

        return $matches[1] ?? (string) $this->id;
    }

    public function documentNumber(?string $prefix = null): string
    {
        $prefix ??= $this->type_document === 'certificat' ? 'CERT' : 'ATT';

        return $prefix.'-'.str_pad($this->serialNumber(), 4, '0', STR_PAD_LEFT);
    }
}
