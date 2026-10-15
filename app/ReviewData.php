<?php

namespace App;

use App\Models\Temoignage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ReviewData
{
    public function googleReviews(): array
    {
        $placeId = config('services.google.place_id');
        $apiKey = config('services.google.key') ?: env('GOOGLE_PLACES_KEY');

        if (blank($placeId) || blank($apiKey)) {
            return [];
        }

        return Cache::remember('google_reviews', 86400, function () use ($placeId, $apiKey) {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => $apiKey,
            ])->connectTimeout(5)
                ->timeout(10)
                ->get("https://places.googleapis.com/v1/places/{$placeId}", [
                    'fields' => 'rating,userRatingCount,reviews',
                ]);

            if (! $response->successful()) {
                return [];
            }

            $payload = $response->json();

            return collect($payload['reviews'] ?? [])
                ->map(function (array $review): array {
                    $text = data_get($review, 'originalText.text') ?? data_get($review, 'text') ?? 'Avis Google client';

                    return [
                        'source' => 'google',
                        'sourceLabel' => 'Google',
                        'author' => data_get($review, 'authorAttribution.displayName') ?? 'Client Google',
                        'city' => 'Google Maps',
                        'rating' => (int) round((float) ($review['rating'] ?? 5)),
                        'text' => (string) $text,
                        'date' => data_get($review, 'publishTime') ?? now()->toDateTimeString(),
                        'verified' => false,
                        'photo' => null,
                        'project' => null,
                    ];
                })
                ->all();
        });
    }

    public function internalReviews(): array
    {
        return Temoignage::query()
            ->published()
            ->orderByDesc('date_projet')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Temoignage $review): array {
                return [
                    'source' => 'interne',
                    'sourceLabel' => 'Client vérifié TRENOU',
                    'author' => $review->nom_client,
                    'city' => $review->ville ?: 'Togo',
                    'rating' => (int) $review->note,
                    'text' => $review->texte,
                    'date' => $review->date_projet?->toDateString() ?? $review->created_at->toDateString(),
                    'verified' => $review->isVerified(),
                    'photo' => $review->photo_projet
                        ? Storage::disk(config('filesystems.default'))->url($review->photo_projet)
                        : null,
                    'project' => $review->projet_ref ?? $review->projet_type,
                ];
            })
            ->all();
    }

    public function mergedReviews(): array
    {
        $reviews = array_merge($this->googleReviews(), $this->internalReviews());

        usort($reviews, function (array $left, array $right): int {
            $leftDate = (string) ($left['date'] ?? '');
            $rightDate = (string) ($right['date'] ?? '');

            return strcmp($rightDate, $leftDate);
        });

        return $reviews;
    }

    public function summary(): array
    {
        $reviews = $this->mergedReviews();

        if ($reviews === []) {
            return [
                'average' => 5.0,
                'count' => 0,
                'distribution' => [
                    5 => 0,
                    4 => 0,
                    3 => 0,
                    2 => 0,
                    1 => 0,
                ],
            ];
        }

        $distribution = [
            5 => 0,
            4 => 0,
            3 => 0,
            2 => 0,
            1 => 0,
        ];

        foreach ($reviews as $review) {
            $rating = max(1, min(5, (int) ($review['rating'] ?? 5)));
            $distribution[$rating] = ($distribution[$rating] ?? 0) + 1;
        }

        $average = round(collect($reviews)->avg(fn (array $review): float => (float) ($review['rating'] ?? 5)), 1);

        return [
            'average' => $average,
            'count' => count($reviews),
            'distribution' => $distribution,
        ];
    }

    public function jsonLd(): array
    {
        $reviews = $this->mergedReviews();
        $summary = $this->summary();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => 'TRENOU',
            'review' => array_values(array_map(function (array $review): array {
                return [
                    '@type' => 'Review',
                    'author' => [
                        '@type' => 'Person',
                        'name' => $review['author'],
                    ],
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => (string) ($review['rating'] ?? 5),
                    ],
                    'reviewBody' => $review['text'],
                ];
            }, array_slice($reviews, 0, 10))),
        ];

        if ($summary['count'] > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => (string) $summary['average'],
                'reviewCount' => (string) $summary['count'],
            ];
        }

        return $schema;
    }
}
