<?php

namespace App\Http\Requests\Editor;

use Illuminate\Foundation\Http\FormRequest;

class BackdropUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'layer' => ['required', 'in:back,front'],
            'image' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:5120'],
        ];
    }
}
