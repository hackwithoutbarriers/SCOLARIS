<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Temoignage extends Model
{
    protected $table = 'temoignages';

    protected $fillable = [
        'devis_id',
        'nom_client',
        'ville',
        'projet_type',
        'projet_ref',
        'note',
        'texte',
        'photo_projet',
        'date_projet',
        'consentement',
        'statut',
        'source',
    ];

    protected $casts = [
        'devis_id' => 'integer',
        'note' => 'integer',
        'consentement' => 'boolean',
        'date_projet' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function devis(): BelongsTo
    {
        return $this->belongsTo(Devis::class);
    }

    public function scopePublished($query)
    {
        $query->where('statut', 'publie')
            ->where('consentement', true);
    }

    public function isVerified(): bool
    {
        return $this->consentement && filled($this->projet_ref ?: $this->devis_id);
    }
}
