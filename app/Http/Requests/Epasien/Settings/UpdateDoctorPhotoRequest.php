<?php

namespace App\Http\Requests\Epasien\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDoctorPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'doctor_code' => ['required', 'string', 'max:20'],
            'doctor_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'doctor_photo_cropped' => ['nullable', 'string'],
            'filter_q' => ['nullable', 'string', 'max:80'],
            'filter_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'doctor_photo.image' => 'File harus berupa gambar.',
            'doctor_photo.mimes' => 'Foto dokter harus berformat JPG, JPEG, PNG, atau WEBP.',
            'doctor_photo.max' => 'Ukuran foto dokter maksimal 2 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'doctor_code' => trim((string) $this->input('doctor_code')),
            'filter_q' => trim((string) $this->input('filter_q')),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->hasFile('doctor_photo') && trim((string) $this->input('doctor_photo_cropped')) === '') {
                $validator->errors()->add('doctor_photo', 'Pilih foto dokter terlebih dahulu.');
            }
        });
    }
}
