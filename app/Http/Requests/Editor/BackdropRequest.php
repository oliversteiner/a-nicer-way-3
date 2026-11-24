<?php

namespace App\Http\Requests\Editor;

use App\Enums\BackdropAlign;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Settings of a backdrop; the image itself comes with the upload only. */
class BackdropRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'layer' => ['required', 'in:back,front'],
            'speed' => ['required', 'numeric', 'min:0.05', 'max:3'],
            'align' => ['required', Rule::enum(BackdropAlign::class)],
            'height' => ['required', 'integer', 'min:10', 'max:100'],
            'repeat' => ['required', 'boolean'],
        ];
    }
}
