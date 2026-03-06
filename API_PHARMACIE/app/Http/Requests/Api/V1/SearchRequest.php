<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
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
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'q.required' => 'Le parametre q est obligatoire.',
            'q.string' => 'Le parametre q doit etre une chaine de caracteres.',
            'q.min' => 'Le parametre q doit contenir au moins 2 caracteres.',
            'q.max' => 'Le parametre q ne doit pas depasser 120 caracteres.',
            'per_page.integer' => 'Le parametre per_page doit etre un entier.',
            'per_page.min' => 'Le parametre per_page doit etre superieur ou egal a 1.',
            'per_page.max' => 'Le parametre per_page ne doit pas depasser 100.',
        ];
    }
}
