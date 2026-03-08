<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFichaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // La autorización se maneja en el controlador con policies
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $ajustes = \DB::connection('site')->table('ajustes')->first();
        $modoAgenciaEventos = $ajustes && $ajustes->modo_operacion === 'agencia_eventos';

        $rules = [
            'descripcion' => 'nullable|max:255',
            'fecha' => 'required|date',
            'tipo' => 'required|integer|in:1,2,3,4',
            'hora' => 'nullable|date_format:H:i',
            'menu' => 'nullable|string|max:500',
            'responsables' => 'nullable|string|max:255',
        ];

        // Reglas específicas para modo agencia de eventos (tipo 4)
        if ($this->input('tipo') == 4 || $modoAgenciaEventos) {
            $rules['precio'] = 'nullable|numeric|min:0';
            $rules['ubicacion_evento'] = 'nullable|string|max:255';
            $rules['aforo_maximo'] = 'nullable|integer|min:1|max:10000';
            $rules['foto_evento'] = 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048';
            $rules['descripcion_evento'] = 'nullable|string|max:1000';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'descripcion.max' => 'La descripción no puede tener más de 255 caracteres.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date' => 'La fecha debe ser una fecha válida.',
            'tipo.required' => 'El tipo de ficha es obligatorio.',
            'tipo.in' => 'El tipo de ficha seleccionado no es válido.',
            'precio.numeric' => 'El precio debe ser un número.',
            'precio.min' => 'El precio no puede ser negativo.',
            'aforo_maximo.min' => 'El aforo máximo debe ser al menos 1.',
            'aforo_maximo.max' => 'El aforo máximo no puede superar 10000.',
            'foto_evento.image' => 'El archivo debe ser una imagen.',
            'foto_evento.mimes' => 'La imagen debe ser formato: jpeg, jpg, png o webp.',
            'foto_evento.max' => 'La imagen no puede superar 2MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $hora = $this->input('hora');
        if (is_string($hora) && $hora !== '') {
            $horaNormalizada = trim($hora);
            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $horaNormalizada)) {
                $horaNormalizada = substr($horaNormalizada, 0, 5);
            }

            $this->merge([
                'hora' => $horaNormalizada
            ]);
        }

        // Si la descripción está vacía, asignar string vacío
        if (empty($this->descripcion)) {
            $this->merge([
                'descripcion' => ''
            ]);
        }
    }
}
