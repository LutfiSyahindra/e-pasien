<?php

namespace App\Http\Requests\Epasien\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLandingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('EPASIEN.SETTINGS.LANDING_PAGE') ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'content' => ['required', 'array:'.implode(',', [...array_keys(config('landing-page.sections')), ...array_keys(config('landing-page.collections'))])],
            'images' => ['sometimes', 'array:'.implode(',', array_keys(config('landing-page.images')))],
            'reset_images' => ['sometimes', 'array:'.implode(',', array_keys(config('landing-page.images')))],
        ];

        foreach (config('landing-page.sections') as $sectionKey => $section) {
            $rules['content.'.$sectionKey] = ['required', 'array:'.implode(',', array_keys($section['fields']))];
            foreach ($section['fields'] as $key => $field) {
                $rules['content.'.$sectionKey.'.'.$key] = $this->fieldRules($field);
            }
        }

        foreach (config('landing-page.collections') as $key => $collection) {
            $rules['content.'.$key] = ['present', 'array', 'list', 'max:30', 'min:'.$collection['min']];
            $rules['content.'.$key.'.*'] = ['array:'.implode(',', array_keys($collection['fields']))];
            foreach ($collection['fields'] as $fieldKey => $field) {
                $rules['content.'.$key.'.*.'.$fieldKey] = $this->fieldRules($field);
            }
        }

        foreach (config('landing-page.images') as $key => $image) {
            $rules['images.'.$key] = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:max_width=6000,max_height=6000'];
            $rules['reset_images.'.$key] = ['sometimes', 'boolean'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach (config('landing-page.sections') as $key => $section) {
            foreach ($section['fields'] as $fieldKey => $field) {
                $attributes['content.'.$key.'.'.$fieldKey] = $field['label'];
            }
        }
        foreach (config('landing-page.collections') as $key => $collection) {
            $attributes['content.'.$key] = $collection['title'];
            foreach ($collection['fields'] as $fieldKey => $field) {
                $attributes['content.'.$key.'.*.'.$fieldKey] = $field['label'];
            }
        }
        foreach (config('landing-page.images') as $key => $image) {
            $attributes['images.'.$key] = $image['label'];
        }

        return $attributes;
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute melebihi batas yang diizinkan (:max).',
            'url' => ':attribute harus berupa URL HTTPS yang valid.',
            'regex' => 'Format :attribute tidak valid.',
            'in' => 'Pilihan :attribute tidak valid.',
            'image' => ':attribute harus berupa gambar.',
            'mimes' => ':attribute harus berformat JPG, PNG, atau WEBP.',
            'content.*.min' => 'Isi minimal satu item pada :attribute.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $content = $this->input('content');
        if (is_array($content)) {
            // HTML form tidak mengirim array kosong ketika semua baris dihapus.
            foreach (array_keys(config('landing-page.collections')) as $key) {
                $content[$key] ??= [];
            }
            $this->merge(['content' => $content]);
        }
    }

    private function fieldRules(array $field): array
    {
        $rules = [($field['optional'] ?? false) ? 'nullable' : 'required', 'string', 'max:'.$field['max']];
        if (isset($field['options'])) {
            $rules[] = Rule::in(array_keys($field['options']));
        }
        if ($field['type'] === 'tel') {
            $rules[] = 'regex:/^\+?(?:[0-9][\s().-]*){6,15}$/';
        }
        if ($field['type'] === 'url') {
            $rules[] = 'url:https';
        }
        if (isset($field['pattern'])) {
            $rules[] = $field['pattern'];
        }

        return $rules;
    }
}
