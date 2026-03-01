<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CerrarMesaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'metodo_pago' => 'required|in:efectivo,tarjeta,mixto',
            'propina' => 'nullable|numeric|min:0|max:999.99',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'metodo_pago.required' => 'Debes seleccionar un método de pago.',
            'metodo_pago.in' => 'El método de pago seleccionado no es válido.',
            'propina.numeric' => 'La propina debe ser un número.',
            'propina.min' => 'La propina no puede ser negativa.',
            'propina.max' => 'La propina es demasiado alta.',
        ];
    }
}
