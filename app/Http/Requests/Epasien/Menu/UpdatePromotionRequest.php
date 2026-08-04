<?php

namespace App\Http\Requests\Epasien\Menu;

class UpdatePromotionRequest extends StorePromotionRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
