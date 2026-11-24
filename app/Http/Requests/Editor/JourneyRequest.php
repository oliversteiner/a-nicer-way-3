<?php

namespace App\Http\Requests\Editor;

use Illuminate\Foundation\Http\FormRequest;

class JourneyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'published' => ['required', 'boolean'],
        ];
    }
}
