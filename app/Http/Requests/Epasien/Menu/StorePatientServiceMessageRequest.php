<?php

namespace App\Http\Requests\Epasien\Menu;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientServiceMessageRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'message' => is_string($this->message) ? trim($this->message) : $this->message,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('EPASIEN.MENU.PASIEN_SERVICE') === true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
