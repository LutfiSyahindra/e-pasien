<?php

namespace App\Http\Requests\Epasien\Menu;

use App\Models\PatientServiceConversation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientServiceConversationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'subject' => is_string($this->subject) ? trim($this->subject) : $this->subject,
            'message' => is_string($this->message) ? trim($this->message) : $this->message,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('EPASIEN.MENU.PASIEN_SERVICE') === true
            && $this->user()?->can('EPASIEN.MENU.PASIEN_SERVICE.KELOLA') !== true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(PatientServiceConversation::CATEGORIES)],
            'subject' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
