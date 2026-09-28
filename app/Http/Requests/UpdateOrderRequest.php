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
            'table_id' => ['nullable', 'integer', 'exists:tables,id'],
            'table_name' => ['nullable', 'string', 'max:100'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'service_charge' => ['nullable', 'boolean'],
            'tax' => ['nullable', 'boolean'],
            'service_charge_percentage' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'service_charge_custom_amount' => ['nullable', 'numeric', 'min:0'],
            'wallet_id_1' => ['nullable', 'integer', 'exists:wallets,id'],
            'wallet_id_2' => ['nullable', 'integer', 'exists:wallets,id'],
            'security_key' => ['nullable', 'string'],
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
            'service_charge_percentage.min' => 'El porcentaje de servicio debe ser al menos 1%.',
            'service_charge_percentage.max' => 'El porcentaje de servicio no puede superar el 100%.',
            'wallet_id_1.exists' => 'La billetera seleccionada no existe.',
            'wallet_id_2.exists' => 'La segunda billetera seleccionada no existe.',
        ];
    }
}
