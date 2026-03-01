<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ajustes;
use Illuminate\Support\Facades\Cache;

class AjustesController extends Controller
{

    public function index()
    {
        $ajustes = Ajustes::on('site')->where('id', 1)->first();
        return view('ajustes.index', compact('ajustes'));
    }

    public function update(Request $request)
    {
        $modo = $request->input('modo_operacion');
        $rules = [
            'precio_invitado' => 'nullable|numeric|min:0',
            'max_invitados_cobrar' => 'nullable|integer|min:0',
            'primer_invitado_gratis' => 'nullable|boolean',
            'activar_invitados_grupo' => 'nullable|boolean',
            'permitir_comprar_sin_stock' => 'nullable|boolean',
            'stock_minimo' => 'nullable|integer|min:0',
            'notificar_stock_bajo' => 'nullable|boolean',
            'facturar_ficha_automaticamente' => 'nullable|boolean',
            'permitir_lectura_codigo_barras' => 'nullable|boolean',
            'modo_operacion' => 'nullable|in:fichas,mesas,agencia_eventos',
            'mostrar_usuarios' => 'nullable|boolean',
            'mostrar_gastos' => 'nullable|boolean',
            'mostrar_compras' => 'nullable|boolean',
            'recordatorio_reservas_minutos' => 'nullable|integer|min:5',
            'recordatorio_reservas_email' => 'required|boolean',
            'recordatorio_reservas_push' => 'required|boolean',
            'recordatorio_reservas_dias' => 'required|integer|min:1',
        ];
        $request->validate($rules);

        $ajustes = Ajustes::on('site')->where('id', 1)->first();

        $ajustes->recordatorio_reservas_dias = $request->input('recordatorio_reservas_dias', 1);
        // Si no viene el campo (por ejemplo, modo mesas), poner valor por defecto
        $ajustes->limite_inscripcion_dias_eventos = $request->has('limite_inscripcion_dias_eventos')
            ? $request->input('limite_inscripcion_dias_eventos', 1)
            : 1;
        $ajustes->recordatorio_reservas_email = $request->input('recordatorio_reservas_email', 1);
        $ajustes->recordatorio_reservas_push = $request->input('recordatorio_reservas_push', 1);

        // Guardar el resto de campos normalmente
        $ajustes->fill($request->except([
            'recordatorio_reservas_dias',
            'limite_inscripcion_dias_eventos',
            'recordatorio_reservas_email',
            'recordatorio_reservas_push',
        ]));
        $ajustes->save();

        // 🚀 OPTIMIZACIÓN: Invalidar caché de ajustes
        $site = get_site();
        Cache::forget("ajustes_site_{$site->uuid}");
        Cache::forget('ajustes_menu');

        return redirect()->route('ajustes.index')->with('success', __('Ajustes actualizados correctamente'));
    }
}
