<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicDevisRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $description = trim(strip_tags((string) $this->input('description_besoin')));

        if ($description === '') {
            $summary = $this->buildDescriptionFromQuote();
            $this->merge(['description_besoin' => $summary]);
        }

        $this->merge([
            'nom' => trim(strip_tags((string) $this->input('nom'))),
            'telephone' => preg_replace('/\s+/', ' ', trim((string) $this->input('telephone'))),
            'ville' => trim(strip_tags((string) $this->input('ville'))),
            'pays' => trim(strip_tags((string) $this->input('pays', 'Togo'))),
            'description_besoin' => $description !== '' ? $description : trim(strip_tags((string) $this->input('description_besoin'))),
            'categorie' => trim((string) $this->input('categorie')),
            'sous_type' => trim((string) $this->input('sous_type')),
            'dimensions' => $this->decodeJsonInput('dimensions'),
            'finition' => trim((string) $this->input('finition')),
            'vitrage' => trim((string) $this->input('vitrage')),
            'options' => $this->decodeJsonInput('options'),
            'estimation' => $this->decodeJsonInput('estimation'),
            'source' => trim((string) $this->input('source', 'simulateur')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nom' => ['bail', 'required', 'string', 'min:2', 'max:255', 'regex:/^[\pL\pM\s\'\-]+$/u'],
            'telephone' => ['bail', 'required', 'string', 'max:30', 'regex:/^(?:\+?[0-9\s\.\-\(\)]{8,20})$/'],
            'ville' => ['nullable', 'string', 'min:2', 'max:255', 'regex:/^[\pL\pM\s\'\-]+$/u'],
            'pays' => ['required', 'string', 'min:2', 'max:80', 'regex:/^[\pL\pM\s\'\-]+$/u'],
            'description_besoin' => ['bail', 'required', 'string', 'min:10', 'max:2000'],
            'categorie' => ['nullable', 'string', Rule::in(array_column(config('pricing.categories', []), 'id'))],
            'sous_type' => ['nullable', 'string', Rule::in(array_column(array_merge(...array_values(config('pricing.subtypes', []))), 'id'))],
            'dimensions' => ['nullable', 'array'],
            'dimensions.largeur' => ['nullable', 'numeric', 'gt:0'],
            'dimensions.hauteur' => ['nullable', 'numeric', 'gt:0'],
            'dimensions.longueur' => ['nullable', 'numeric', 'gt:0'],
            'finition' => ['nullable', 'string', Rule::in(array_column(config('pricing.finitions', []), 'id'))],
            'vitrage' => ['nullable', 'string', Rule::in(array_column(config('pricing.vitrages', []), 'id'))],
            'options' => ['nullable', 'array', 'max:20'],
            'options.*' => ['string', Rule::in(array_column(config('pricing.options', []), 'id'))],
            'estimation' => ['nullable', 'array'],
            'estimation.min' => ['nullable', 'integer', 'min:0'],
            'estimation.max' => ['nullable', 'integer', 'gte:estimation.min'],
            'estimation.devise' => ['nullable', 'string', 'max:10'],
            'source' => ['nullable', 'string', 'max:80'],
        ];
    }

    private function buildDescriptionFromQuote(): string
    {
        $categorie = $this->input('categorie');
        $sousType = $this->input('sous_type');
        $dimensions = $this->decodeJsonInput('dimensions');
        $finition = $this->input('finition');
        $vitrage = $this->input('vitrage');
        $options = $this->decodeJsonInput('options');

        $summary = ['Demande de devis'];

        if ($categorie !== null && $categorie !== '') {
            $summary[] = 'Catégorie : '.ucfirst((string) $categorie);
        }

        if ($sousType !== null && $sousType !== '') {
            $summary[] = 'Produit : '.str_replace('-', ' ', (string) $sousType);
        }

        if (is_array($dimensions) && ! empty($dimensions)) {
            $measureSummary = [];
            foreach (['largeur', 'hauteur', 'longueur'] as $field) {
                if (isset($dimensions[$field]) && $dimensions[$field] !== '') {
                    $measureSummary[] = $field.' : '.$dimensions[$field];
                }
            }

            if ($measureSummary !== []) {
                $summary[] = 'Dimensions : '.implode(' | ', $measureSummary);
            }
        }

        if ($finition !== null && $finition !== '') {
            $summary[] = 'Finition : '.ucfirst((string) $finition);
        }

        if ($vitrage !== null && $vitrage !== '') {
            $summary[] = 'Vitrage : '.ucfirst((string) $vitrage);
        }

        if (is_array($options) && ! empty($options)) {
            $summary[] = 'Options : '.implode(', ', array_map('strval', $options));
        }

        return implode(' — ', $summary);
    }

    private function decodeJsonInput(string $key): mixed
    {
        $value = $this->input($key);

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est requis.',
            'nom.regex' => 'Le nom contient des caractères non autorisés.',
            'telephone.required' => 'Le numéro de téléphone est requis.',
            'telephone.regex' => 'Le numéro de téléphone est invalide.',
            'ville.regex' => 'La ville contient des caractères non autorisés.',
            'pays.required' => 'Le pays est requis.',
            'pays.regex' => 'Le pays contient des caractères non autorisés.',
            'description_besoin.required' => 'La description du besoin est requise.',
            'description_besoin.min' => 'La description du besoin doit contenir au moins 10 caractères.',
        ];
    }
}
