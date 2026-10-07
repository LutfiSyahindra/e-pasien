<?php

namespace App\Http\Requests\Epasien\Settings;

class UpdateDoctorScheduleLeaveRequest extends UpdateDoctorScheduleRequest
{
    public function rules(): array
    {
        return [
            ...array_intersect_key(parent::rules(), array_flip([
                'original_doctor_code', 'original_day', 'original_start_time',
                'filter_q', 'filter_day', 'filter_clinic', 'filter_page',
            ])),
            'is_on_leave' => ['required', 'boolean'],
        ];
    }
}
