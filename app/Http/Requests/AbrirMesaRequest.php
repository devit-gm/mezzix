<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AbrirMesaRequest extends FormRequest
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
            'numero_comensales' => 'required|integer|min:1|max:20',
            'notas' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'numero_comensales.required' => 'El número de comensales es obligatorio.',
            'numero_comensales.integer' => 'El número de comensales debe ser un número entero.',
            'numero_comensales.min' => 'Debe haber al menos 1 comensal.',
            'numero_comensales.max' => 'No se permiten más de 20 comensales por mesa.',
            'notas.max' => 'Las notas no pueden tener más de 500 caracteres.',
        ];
    }
}
