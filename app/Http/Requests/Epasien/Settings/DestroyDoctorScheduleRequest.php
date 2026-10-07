<?php

namespace App\Http\Requests\Epasien\Settings;

class DestroyDoctorScheduleRequest extends UpdateDoctorScheduleRequest
{
    public function rules(): array
    {
        return [
            ...array_intersect_key(parent::rules(), array_flip([
                'original_doctor_code', 'original_day', 'original_start_time',
                'filter_q', 'filter_day', 'filter_clinic',
            ])),
            'original_clinic_code' => ['required', 'string', 'max:5'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge(['original_clinic_code' => trim((string) $this->input('original_clinic_code'))]);
    }
}
