<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'table_id' => ['nullable', 'integer', 'exists:tables,id'],
            'table_name' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'in:cash,transfer,card'],
            'payment_amount_1' => ['nullable', 'numeric', 'min:0'],
            'payment_method_2' => ['nullable', 'in:cash,transfer,card', 'different:payment_method'],
            'payment_amount_2' => ['nullable', 'numeric', 'min:0', 'required_with:payment_method_2'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Debes agregar al menos un producto a la orden.',
            'items.min' => 'La orden debe tener al menos un producto.',
            'items.*.product_id.exists' => 'Uno de los productos seleccionados no existe.',
            'items.*.quantity.min' => 'La cantidad mínima por producto es 1.',
            'payment_method_2.different' => 'Los dos métodos de pago no pueden ser iguales.',
            'payment_amount_2.required_with' => 'Debes indicar el monto del segundo método de pago.',
        ];
    }
}
