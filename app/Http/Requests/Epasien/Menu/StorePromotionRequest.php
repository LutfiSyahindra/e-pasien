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
            'category' => ['required', Rule::in(Promotion::CATEGORIES)],
            'title' => ['required', 'string', 'max:120'],
            'caption' => ['required', 'string', 'max:2000'],
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
                'dimensions:max_width=4096,max_height=4096',
            ],
            'starts_at' => ['required', 'date'],
            'duration_value' => ['required', 'integer', 'min:1', 'max:9999'],
            'duration_unit' => ['required', Rule::in(Promotion::DURATION_UNITS)],
            'status' => ['required', Rule::in([Promotion::STATUS_DRAFT, Promotion::STATUS_PUBLISHED])],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Kategori konten wajib dipilih.',
            'category.in' => 'Kategori konten harus berupa Promosi atau Informasi.',
            'title.required' => 'Judul konten wajib diisi.',
            'caption.required' => 'Caption konten wajib diisi.',
            'image.required' => 'Gambar konten wajib dipilih.',
            'image.image' => 'Berkas harus berupa gambar yang valid.',
            'image.mimes' => 'Gunakan gambar JPG, PNG, atau WebP.',
            'image.max' => 'Ukuran gambar maksimal 5 MB.',
            'image.dimensions' => 'Resolusi gambar maksimal 4096 × 4096 piksel.',
            'starts_at.required' => 'Waktu mulai tayang wajib diisi.',
            'duration_value.required' => 'Durasi konten wajib diisi.',
            'duration_value.min' => 'Durasi minimal adalah 1.',
        ];
    }
}
