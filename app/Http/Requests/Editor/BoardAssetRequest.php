<?php

namespace App\Http\Requests\Editor;

use App\Enums\AssetLayer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BoardAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'layer' => ['required', Rule::enum(AssetLayer::class)],
            'image' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:5120'],
        ];
    }
}
