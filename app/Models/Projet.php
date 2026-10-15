<?php

namespace App\Models;

use App\Services\PortfolioImageOptimizer;
use Illuminate\Database\Eloquent\Model;

class Projet extends Model
{
    protected $fillable = [
        'titre',
        'categorie',
        'description',
        'ville',
        'pays',
        'is_visible_public',
        'code_suivi_diaspora',
        'images',
    ];

    protected $casts = [
        'is_visible_public' => 'boolean',
        'images' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $project): void {
            if (! is_array($project->images) || empty($project->images)) {
                return;
            }

            $project->images = app(PortfolioImageOptimizer::class)->optimize($project->images);
        });
    }
}
