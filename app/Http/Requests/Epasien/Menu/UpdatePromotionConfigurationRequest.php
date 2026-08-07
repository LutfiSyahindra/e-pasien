<?php

namespace App\Http\Requests\Epasien\Menu;

use App\Models\Promotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePromotionConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('EPASIEN.MENU.PROMOSI.KELOLA') === true;
    }

    public function rules(): array
    {
        return [
            'default_duration_value' => ['required', 'integer', 'min:1', 'max:9999'],
            'default_duration_unit' => ['required', Rule::in(Promotion::DURATION_UNITS)],
        ];
    }

    public function messages(): array
    {
        return [
            'default_duration_value.required' => 'Durasi bawaan konten wajib diisi.',
            'default_duration_value.min' => 'Durasi bawaan minimal adalah 1.',
        ];
    }
}
