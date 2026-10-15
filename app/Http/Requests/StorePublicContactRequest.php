<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePublicContactRequest extends FormRequest
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
        $this->merge([
            'nom' => trim(strip_tags((string) $this->input('nom'))),
            'email' => strtolower(trim((string) $this->input('email'))),
            'telephone' => preg_replace('/\s+/', ' ', trim((string) $this->input('telephone'))),
            'sujet' => trim(strip_tags((string) $this->input('sujet'))),
            'message' => trim(strip_tags((string) $this->input('message'))),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom' => ['bail', 'required', 'string', 'min:2', 'max:255', 'regex:/^[\pL\pM\s\'\-]+$/u'],
            'email' => ['bail', 'required', 'email', 'max:255'],
            'telephone' => ['bail', 'nullable', 'string', 'max:30', 'regex:/^(?:\+?[0-9\s\.\-\(\)]{8,20})$/'],
            'sujet' => ['bail', 'required', 'string', 'min:2', 'max:255'],
            'message' => ['bail', 'required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est requis.',
            'nom.regex' => 'Le nom contient des caractères non autorisés.',
            'email.required' => 'L’adresse e-mail est requise.',
            'email.email' => 'L’adresse e-mail est invalide.',
            'telephone.regex' => 'Le numéro de téléphone est invalide.',
            'sujet.required' => 'Le sujet du message est requis.',
            'message.required' => 'Le message est requis.',
            'message.min' => 'Le message doit contenir au moins 10 caractères.',
        ];
    }
}
