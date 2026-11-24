<?php

namespace App\Http\Requests\Editor;

use App\Enums\CharacterPose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CharacterImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'pose' => ['required', Rule::enum(CharacterPose::class)],
            'image' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:5120'],
        ];
    }
}
