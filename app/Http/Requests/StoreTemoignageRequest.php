<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemoignageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nom_client' => trim(strip_tags((string) $this->input('nom_client'))),
            'ville' => trim(strip_tags((string) $this->input('ville'))),
            'projet_type' => trim(strip_tags((string) $this->input('projet_type'))),
            'projet_ref' => trim(strip_tags((string) $this->input('projet_ref'))),
            'texte' => trim(strip_tags((string) $this->input('texte'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'nom_client' => ['required', 'string', 'min:2', 'max:255'],
            'ville' => ['nullable', 'string', 'min:2', 'max:255'],
            'projet_type' => ['nullable', 'string', 'max:255'],
            'projet_ref' => ['nullable', 'string', 'max:255'],
            'note' => ['required', 'integer', 'min:1', 'max:5'],
            'texte' => ['required', 'string', 'min:20', 'max:2000'],
            'date_projet' => ['nullable', 'date'],
            'photo_projet' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'consentement' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom_client.required' => 'Le nom du client est requis.',
            'note.required' => 'La note est obligatoire.',
            'texte.required' => 'Le témoignage est requis.',
            'texte.min' => 'Le témoignage doit contenir au moins 20 caractères.',
            'photo_projet.required' => 'Une photo du projet est obligatoire pour publier le témoignage.',
            'photo_projet.image' => 'Le fichier doit être une image.',
            'consentement.accepted' => 'Vous devez valider le consentement avant publication.',
        ];
    }
}
