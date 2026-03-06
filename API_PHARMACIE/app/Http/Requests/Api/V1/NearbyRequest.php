<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class NearbyRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:0.1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lat.required' => 'Le parametre lat est obligatoire.',
            'lat.numeric' => 'Le parametre lat doit etre numerique.',
            'lat.between' => 'Le parametre lat doit etre compris entre -90 et 90.',
            'lng.required' => 'Le parametre lng est obligatoire.',
            'lng.numeric' => 'Le parametre lng doit etre numerique.',
            'lng.between' => 'Le parametre lng doit etre compris entre -180 et 180.',
            'radius_km.numeric' => 'Le parametre radius_km doit etre numerique.',
            'radius_km.min' => 'Le parametre radius_km doit etre superieur ou egal a 0.1.',
            'radius_km.max' => 'Le parametre radius_km ne doit pas depasser 100.',
            'per_page.integer' => 'Le parametre per_page doit etre un entier.',
            'per_page.min' => 'Le parametre per_page doit etre superieur ou egal a 1.',
            'per_page.max' => 'Le parametre per_page ne doit pas depasser 100.',
        ];
    }
}
