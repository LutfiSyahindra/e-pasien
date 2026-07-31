<?php

namespace App\Http\Requests\Epasien\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorScheduleRequest extends FormRequest
{
    private const DAYS = [
        'SENIN',
        'SELASA',
        'RABU',
        'KAMIS',
        'JUMAT',
        'SABTU',
        'AKHAD',
    ];

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
            'original_doctor_code' => ['required', 'string', 'max:20'],
            'original_day' => ['required', 'string', Rule::in(self::DAYS)],
            'original_start_time' => ['required', 'date_format:H:i'],
            'day' => ['required', 'string', Rule::in(self::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'quota' => ['required', 'integer', 'min:0', 'max:9999'],
            'filter_q' => ['nullable', 'string', 'max:80'],
            'filter_day' => [
                'nullable',
                'string',
                Rule::in(['SEMUA', ...self::DAYS]),
            ],
            'filter_clinic' => ['nullable', 'string', 'max:5'],
            'filter_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'day' => 'hari kerja',
            'start_time' => 'jam mulai',
            'end_time' => 'jam selesai',
            'quota' => 'kuota',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
            'quota.min' => 'Kuota tidak boleh bernilai negatif.',
            'quota.max' => 'Kuota maksimal adalah 9.999 pasien.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'original_doctor_code' => trim((string) $this->input('original_doctor_code')),
            'original_day' => strtoupper(trim((string) $this->input('original_day'))),
            'day' => strtoupper(trim((string) $this->input('day'))),
            'filter_q' => trim((string) $this->input('filter_q')),
            'filter_day' => strtoupper(trim((string) $this->input('filter_day'))),
            'filter_clinic' => trim((string) $this->input('filter_clinic')),
        ]);
    }
}
