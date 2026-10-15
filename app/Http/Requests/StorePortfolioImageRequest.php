<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePortfolioImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['required', 'file', 'image', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Au moins une image doit être fournie.',
            'images.max' => 'Vous ne pouvez téléverser que 10 images maximum.',
            'images.*.image' => 'Chaque fichier doit être une image valide.',
            'images.*.mimetypes' => 'Les images doivent être au format JPG, PNG ou WebP.',
            'images.*.max' => 'Chaque image ne doit pas dépasser 2 Mo.',
        ];
    }
}
