<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => [
                'required',
                'string',
                'min:2',
                'max:255'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'search.required' => 'Search keyword is required.',
            'search.min' => 'Search keyword must be at least 2 characters.',
        ];
    }
}