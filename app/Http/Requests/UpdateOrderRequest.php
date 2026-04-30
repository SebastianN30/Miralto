<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
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
            'status' => ['required', 'in:pending,paid,cancelled'],
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
            'status.required' => 'El estado de la orden es obligatorio.',
            'status.in' => 'El estado debe ser: pendiente, pagado o cancelado.',
            'payment_method_2.different' => 'Los dos métodos de pago no pueden ser iguales.',
            'payment_amount_2.required_with' => 'Debes indicar el monto del segundo método de pago.',
        ];
    }
}
