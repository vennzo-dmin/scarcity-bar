<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslationsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'translations'   => ['nullable', 'array'],
            'translations.*' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
