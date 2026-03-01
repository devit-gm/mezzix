<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Services\FirebaseService;

class ContactoController extends Controller
{
    public function index()
    {
        return view('contacto.index');
    }

    public function send(Request $request)
    {
        $request->validate([
            'asunto' => 'required|string|max:255',
            'mensaje' => 'required|string|max:2000',
        ]);

        // Verificar que hay configuración de correo
        if (!config('mail.mailers.smtp.host')) {
            return redirect()->back()->with('error', __('No hay configuración de correo. Contacte con el administrador del sistema.'));
        }

        $user = Auth::user();
        $data = [
            'asunto' => $request->asunto,
            'mensaje' => $request->mensaje,
            'usuario' => $user->name,
            'email' => $user->email,
        ];

        try {
            // Obtener el email del sitio actual
            $site = get_site();
            
            // Obtener emails de administradores del sitio
            $administradores = User::where('site_id', $site->id)
                ->where('role_id', 2) // role_id 2 = administrador
                ->whereNotNull('email')
                ->get();

            if ($administradores->isEmpty()) {
                return redirect()->back()->with('error', __('No hay administradores configurados para recibir mensajes.'));
            }

            // La configuración del correo ya está establecida por MailConfigServiceProvider
            // que usa los datos del sitio actual
            foreach ($administradores as $admin) {
                Mail::send('emails.contacto', $data, function ($message) use ($admin, $data) {
                    $message->to($admin->email, $admin->name)
                        ->subject($data['asunto']);
                    // No especificar from() para usar la configuración global del sitio
                });
            }

            // Enviar notificación push al administrador del sitio
            $this->enviarNotificacionAdministrador($site, $data);

            return redirect()->back()->with('success', __('Mensaje enviado correctamente.'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Error al enviar el mensaje: ') . $e->getMessage());
        }
    }

    private function enviarNotificacionAdministrador($site, $data)
    {
        try {
            $firebase = new FirebaseService();
            
            // Obtener usuarios administradores del sitio con token FCM
            $administradores = User::where('site_id', $site->id)
                ->where('role_id', 2)
                ->whereNotNull('fcm_token')
                ->get();

            // Si no hay administradores, no enviar notificaciones
            if ($administradores->isEmpty()) {
                return;
            }

            // Array para evitar duplicados
            $tokensEnviados = [];

            foreach ($administradores as $admin) {
                // Evitar duplicados por token
                if (in_array($admin->fcm_token, $tokensEnviados)) {
                    continue;
                }

                $firebase->sendNotification(
                    $admin->fcm_token,
                    'Contacto',
                    '💬 Se ha recibido un nuevo mensaje',
                    [
                        'type' => 'contacto',
                        'asunto' => $data['asunto'],
                        'usuario' => $data['usuario'],
                        'email' => $data['email'],
                        'url' => route('contacto.index')
                    ]
                );

                $tokensEnviados[] = $admin->fcm_token;
            }
        } catch (\Exception $e) {
            // Log error pero no interrumpir el flujo
            \Log::warning('Error al enviar notificación de contacto: ' . $e->getMessage());
        }
    }
}
