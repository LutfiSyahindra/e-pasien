<?php

namespace App\Http\Requests\Epasien\Menu;

use App\Models\Promotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('EPASIEN.MENU.PROMOSI.KELOLA') === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'caption' => ['required', 'string', 'max:2000'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'starts_at' => ['required', 'date'],
            'duration_value' => ['required', 'integer', 'min:1', 'max:9999'],
            'duration_unit' => ['required', Rule::in(Promotion::DURATION_UNITS)],
            'status' => ['required', Rule::in([Promotion::STATUS_DRAFT, Promotion::STATUS_PUBLISHED])],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul promosi wajib diisi.',
            'caption.required' => 'Caption promosi wajib diisi.',
            'image.required' => 'Gambar promosi wajib dipilih.',
            'image.image' => 'Berkas harus berupa gambar yang valid.',
            'image.mimes' => 'Gunakan gambar JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran gambar maksimal 5 MB.',
            'starts_at.required' => 'Waktu mulai tayang wajib diisi.',
            'duration_value.required' => 'Durasi promosi wajib diisi.',
            'duration_value.min' => 'Durasi minimal adalah 1.',
        ];
    }
}
