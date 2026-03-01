<?php

namespace App\Services;

use App\Models\Producto;
use App\Models\Ajustes;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class StockNotificationService
{
    protected $firebase;

    public function __construct()
    {
        $this->firebase = new FirebaseService();
    }

    /**
     * Verifica el stock de un producto y envía notificaciones si está bajo
     */
    public function verificarYNotificar($productoUuid)
    {
        try {
            $ajustes = Ajustes::first();
            
            Log::info('=== VERIFICAR STOCK ===', [
                'producto_uuid' => $productoUuid,
                'ajustes_existe' => $ajustes ? 'sí' : 'no',
                'notificar_stock_bajo' => $ajustes ? ($ajustes->notificar_stock_bajo ?? 'no definido') : 'no ajustes',
                'stock_minimo' => $ajustes ? ($ajustes->stock_minimo ?? 'no definido') : 'no ajustes'
            ]);
            
            // Si las notificaciones están desactivadas, no hacer nada
            if (!$ajustes || !$ajustes->notificar_stock_bajo) {
                Log::info('Notificaciones de stock bajo desactivadas o sin ajustes');
                return;
            }

            $producto = Producto::with('familiaObj')->find($productoUuid);
            
            if (!$producto) {
                Log::warning('Producto no encontrado: ' . $productoUuid);
                return;
            }

            Log::info('Producto encontrado', [
                'nombre' => $producto->nombre,
                'stock' => $producto->stock,
                'stock_minimo' => $ajustes->stock_minimo,
                'debe_notificar' => $producto->stock <= $ajustes->stock_minimo
            ]);

            // Verificar si el stock está por debajo o igual al mínimo
            if ($producto->stock <= $ajustes->stock_minimo) {
                Log::info('Enviando notificaciones de stock bajo para: ' . $producto->nombre);
                $this->enviarNotificaciones($producto, $ajustes);
            }
        } catch (\Exception $e) {
            Log::error('Error en verificarYNotificar: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Verifica todos los productos y notifica los que tengan stock bajo
     */
    public function verificarTodosLosProductos()
    {
        $ajustes = Ajustes::first();
        
        if (!$ajustes || !$ajustes->notificar_stock_bajo) {
            return;
        }

        $productos = Producto::with('familiaObj')
            ->where('combinado', 0)
            ->where('stock', '<=', $ajustes->stock_minimo)
            ->get();

        foreach ($productos as $producto) {
            $this->enviarNotificaciones($producto, $ajustes);
        }
    }

    /**
     * Envía notificaciones por email y push a administradores
     */
    protected function enviarNotificaciones($producto, $ajustes)
    {
        try {
            $site = get_site();
            
            Log::info('Obteniendo usuarios para notificar', [
                'site_id' => $site->id,
                'site_nombre' => $site->nombre
            ]);
            
            // Obtener usuarios con role_id < 4 (administradores y gerentes)
            $usuarios = User::where('site_id', $site->id)
                ->where('role_id', '<', 4)
                ->whereNotNull('email')
                ->get();

            Log::info('Usuarios encontrados para email: ' . $usuarios->count());

            if ($usuarios->isEmpty()) {
                Log::warning('No hay usuarios para notificar por email');
                return;
            }
        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios: ' . $e->getMessage());
            return;
        }

        $data = [
            'producto_nombre' => $producto->nombre,
            'stock_actual' => $producto->stock,
            'stock_minimo' => $ajustes->stock_minimo,
            'familia' => $producto->familiaObj ? $producto->familiaObj->nombre : 'Sin familia',
            'site_nombre' => $site->nombre
        ];

        // Enviar emails
        foreach ($usuarios as $usuario) {
            try {
                Mail::send('emails.stock-bajo', $data, function ($message) use ($usuario, $producto) {
                    $message->to($usuario->email, $usuario->name)
                        ->subject('⚠️ Alerta de Stock Bajo: ' . $producto->nombre);
                });
            } catch (\Exception $e) {
                Log::warning('Error al enviar email de stock bajo: ' . $e->getMessage());
            }
        }

        // Enviar notificaciones push a usuarios con token FCM
        try {
            $usuariosConFCM = User::where('site_id', $site->id)
                ->where('role_id', '<', 4)
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->get();

            Log::info('Usuarios encontrados para notificación push: ' . $usuariosConFCM->count());

            $tokensEnviados = [];
            $enviadas = 0;
            
            foreach ($usuariosConFCM as $usuario) {
                // Evitar duplicados por token
                if (in_array($usuario->fcm_token, $tokensEnviados)) {
                    continue;
                }

                try {
                    Log::info('Enviando notificación push a: ' . $usuario->name);
                    
                    $this->firebase->sendNotification(
                        $usuario->fcm_token,
                        'Alerta de Stock',
                        '⚠️ Stock bajo: ' . $producto->nombre . ' (' . $producto->stock . ' unidades)',
                        [
                            'type' => 'stock_bajo',
                            'producto_uuid' => $producto->uuid,
                            'producto_nombre' => $producto->nombre,
                            'stock_actual' => $producto->stock,
                            'url' => route('productos.inventory')
                        ]
                    );

                    $tokensEnviados[] = $usuario->fcm_token;
                    $enviadas++;
                    
                    Log::info('Notificación push enviada correctamente a: ' . $usuario->name);
                } catch (\Exception $e) {
                    Log::error('Error al enviar notificación FCM a ' . $usuario->name . ': ' . $e->getMessage());
                }
            }
            
            Log::info('Total notificaciones push enviadas: ' . $enviadas);
        } catch (\Exception $e) {
            Log::error('Error al enviar notificaciones push: ' . $e->getMessage());
        }
    }
}
