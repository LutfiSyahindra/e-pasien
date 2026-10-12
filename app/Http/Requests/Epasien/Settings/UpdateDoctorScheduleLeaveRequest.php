<?php

namespace App\Http\Requests\Epasien\Settings;

use App\Support\Epasien\DoctorScheduleDay;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'leave_date' => [
                'required',
                'date_format:Y-m-d',
                Rule::when($this->boolean('is_on_leave'), ['after_or_equal:'.now('Asia/Jakarta')->toDateString()]),
            ],
            'original_leave_date' => ['nullable', 'date_format:Y-m-d', 'prohibited_unless:is_on_leave,1'],
        ];
    }

    public function attributes(): array
    {
        return [...parent::attributes(), 'leave_date' => 'tanggal libur', 'original_leave_date' => 'tanggal libur sebelumnya'];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->boolean('is_on_leave')) {
                return;
            }

            $date = Carbon::createFromFormat('!Y-m-d', $this->input('leave_date'), 'Asia/Jakarta');
            $day = DoctorScheduleDay::EPASIEN_DAYS[$date->isoWeekday() - 1];
            if ($day !== $this->input('original_day')) {
                $label = ucfirst(strtolower($this->input('original_day')));
                $validator->errors()->add('leave_date', 'Pilih tanggal '.$label.' sesuai jadwal praktik yang dipilih.');
            }
        });
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'leave_date.required' => 'Pilih tanggal libur dokter terlebih dahulu.',
            'leave_date.date_format' => 'Tanggal libur dokter tidak valid.',
            'leave_date.after_or_equal' => 'Tanggal libur tidak boleh sebelum hari ini.',
        ];
    }
}
