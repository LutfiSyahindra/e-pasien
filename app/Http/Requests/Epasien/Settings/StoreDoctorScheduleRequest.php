<?php

namespace App\Http\Requests\Epasien\Settings;

class StoreDoctorScheduleRequest extends UpdateDoctorScheduleRequest
{
    public function rules(): array
    {
        return [
            ...array_intersect_key(parent::rules(), array_flip([
                'day', 'start_time', 'end_time', 'quota',
            ])),
            'doctor_code' => ['required', 'string', 'max:20'],
            'clinic_code' => ['required', 'string', 'max:5'],
        ];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'doctor_code' => 'dokter', 'clinic_code' => 'poliklinik'];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'doctor_code' => trim((string) $this->input('doctor_code')),
            'clinic_code' => trim((string) $this->input('clinic_code')),
        ]);
    }
}
