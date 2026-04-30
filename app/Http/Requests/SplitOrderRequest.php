<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SplitOrderRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Debes seleccionar al menos un item para dividir.',
            'items.*.order_item_id.exists' => 'Uno de los items seleccionados no existe.',
            'items.*.quantity.min' => 'La cantidad a dividir debe ser al menos 1.',
        ];
    }
}
