<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarMesaRequest extends FormRequest
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
            'descripcion' => 'required|string|max:100',
            'numero_mesa' => 'required|integer|min:1|max:999',
            'numero_comensales' => 'nullable|integer|min:1|max:50',
            'observaciones' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.max' => 'La descripción no puede tener más de 100 caracteres.',
            'numero_mesa.required' => 'El número de mesa es obligatorio.',
            'numero_mesa.integer' => 'El número de mesa debe ser un número entero.',
            'numero_mesa.min' => 'El número de mesa debe ser al menos 1.',
            'numero_mesa.max' => 'El número de mesa no puede ser mayor a 999.',
            'numero_comensales.integer' => 'El número de comensales debe ser un número entero.',
            'numero_comensales.min' => 'Debe haber al menos 1 comensal.',
            'numero_comensales.max' => 'No se permiten más de 50 comensales.',
            'observaciones.max' => 'Las observaciones no pueden tener más de 255 caracteres.',
        ];
    }
}
