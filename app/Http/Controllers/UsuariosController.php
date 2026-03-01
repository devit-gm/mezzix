<?php

namespace App\Http\Controllers;

use App\Models\FichaUsuario;
use App\Models\License;
use App\Models\Reserva;
use App\Models\Role;
use App\Models\Site;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\TwilioService;
use App\Services\VerificarRolesService;
use Illuminate\Console\View\Components\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Services\ImageService;


class UsuariosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    protected $verificarRolesService;
    protected $twilio;

    public function __construct(TwilioService $twilio)
    {
        $this->middleware(function ($request, $next) {
            $usuario = Auth::user();
            $domain = $request->getHost();
            $site = Site::where('dominio', $domain)->first();
            if ($request->route()->getName() != 'usuarios.edit' && $request->route()->getName() != 'usuarios.update') {
                if ($usuario->role_id > 2) {
                    abort(403, "No tiene acceso a este recurso.");
                }
            }

            return $next($request);
        });
        $this->twilio = $twilio;
    }

    public function index()
    {
        $site = get_site();
        if(Auth::user()->role_id > 1){
            $usuarios = User::where('site_id', $site->id)->where('role_id', '>', 1)->orderBy('id')->get();
        }else{
            $usuarios = User::where('site_id', $site->id)->orderBy('id')->get();
        }
        
        $roles = Role::orderBy('id')->get();
        
        // Obtener usuarios que tienen fichas o reservas en una sola consulta
        $usuariosConFichas = FichaUsuario::whereIn('user_id', $usuarios->pluck('uuid'))
            ->distinct()
            ->pluck('user_id')
            ->toArray();
        
        $usuariosConReservas = Reserva::whereIn('user_id', $usuarios->pluck('uuid'))
            ->distinct()
            ->pluck('user_id')
            ->toArray();
        
        foreach ($usuarios as $usuario) {
            if ($usuario->role_id == 1) {
                $usuario->borrable = false;
            } else {
                //Si el usuario está en FichaUsuario o en Reservas no se puede borrar
                $usuario->borrable = !in_array($usuario->uuid, $usuariosConFichas) && !in_array($usuario->uuid, $usuariosConReservas);
            }
        }
        return view('usuarios.index', compact('usuarios', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:3'],
            'image' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
            'role_id' => 'required',
            'phone_number' => ['required', 'unique:users']
        ]);
        
        // Procesar y redimensionar imagen
        $imageName = ImageService::processAndSave($request->image, 'public/images');
        
        // Copiar a public/images para compatibilidad
        $sourcePath = storage_path('app/public/images/' . $imageName);
        $destPath = public_path('images/' . $imageName);
        copy($sourcePath, $destPath);

        $domain = $request->getHost();
        $site = Site::where('dominio', $domain)->first();

        User::create([
            'name' => $request->name,
            'image' => $imageName,
            'password' => Hash::make($request->password),
            'email' => $request->email,
            'role_id' => $request->role_id,
            'phone_number' => $request->phone_number,
            'site_id' => $site->id,
            'locale' => $request->locale ?? 'es'
        ]);

        //generamos una nueva licencia
        $licenseKey = $this->generateLicenseKey();
        $usuario = User::where('email', $request->email)->first();
        //Damos de alta la licencia en la tabla de licencias
        License::create([
            'license_key' => $licenseKey,
            'user_id' => $usuario->id,
            'site_id' => $site->id,
            'actived' => 0,
            'expires_at' => date('Y-m-d', strtotime('+1 year'))
        ]);


        //Enviamos un correo al usuario con la licencia
        $data = array('name' => $request->name, 'licenseKey' => $licenseKey);
        Mail::send('emails.license', $data, function ($message) use ($request) {
            $message->to($request->email, $request->name)
                ->subject('Nueva licencia para ' . siteName());
            // No especificar from() para usar la configuración global de mail.from
        });



        return redirect()->route('usuarios.index')
            ->with('success', __('Usuario creado con éxito.'));
    }

    private function generateLicenseKey($length = 16)
    {
        return strtoupper(bin2hex(random_bytes($length / 2)));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $usuario = User::find($id);
        return view('usuarios.show', compact('usuario'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $usuario = User::find($id);
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $usuario->id],
            'image' => 'image|mimes:png,jpg,jpeg,webp|max:2048',
            'role_id' => 'required',
            'phone_number' => ['required', 'unique:users,email,' . $usuario->phone_number],
            'password' => 'required|string|min:8'
        ]);


        if ($request->image != null) {
            if (File::exists(public_path('images') . '/'  . $usuario->image)) {
                File::delete(public_path('images') . '/'  . $usuario->image);
            }
            if (File::exists(storage_path('app/public/images') . '/'  . $usuario->image)) {
                File::delete(storage_path('app/public/images') . '/'  . $usuario->image);
            }
            
            // Procesar y redimensionar imagen
            $imageName = ImageService::processAndSave($request->image, 'public/images');
            
            // Copiar a public/images para compatibilidad
            $sourcePath = storage_path('app/public/images/' . $imageName);
            $destPath = public_path('images/' . $imageName);
            copy($sourcePath, $destPath);
        } else {
            $imageName = $usuario->image;
        }
        $password = $request->password;
        if ($usuario->password != $request->password) {
            $password = Hash::make($request->password);
        } else {
            $password = $usuario->password;
        }

        $usuario->update([
            'name' => $request->name,
            'image' => $imageName,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'phone_number' => $request->phone_number,
            'password' => $password,
            'locale' => $request->locale ?? $usuario->locale ?? 'es'
        ]);
        // $to = "+"  . $request->phone_number;
        // $message = "SMS de prueba desde Laravel";

        // $sent = $this->twilio->sendSms($to, $message);
        if ($usuario->role_id == 4) {
            return redirect()->route('usuarios.edit', $usuario->id)
                ->with('success', __('Usuario actualizado con éxito.'));
        } else {
            return redirect()->route('usuarios.index')
                ->with('success', __('Usuario actualizado con éxito.'));
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $usuario = User::find($id);
        if (File::exists(public_path('images') . '/'  . $usuario->image)) {
            File::delete(public_path('images') . '/'  . $usuario->image);
        }
        $usuario->delete();
        return redirect()->route('usuarios.index')
            ->with('success', __('Usuario eliminado con éxito'));
    }

    /**
     * Show the form for creating a new post.
     */
    public function create()
    {
        $usuario = User::orderBy('name')->get();
        $roles = Role::where('id', '>', 1)->orderBy('Name')->get();
        return view('usuarios.create', compact('usuario', 'roles'));
    }

    /**
     * Show the form for editing the specified post.
     *
     * @param  int  $id
     */
    public function edit($id)
    {
        $usuario = User::find($id);
        if ($usuario->role_id == 1) {
            $usuario->borrable = false;
        } else {
            //Si el usuario está en FichaUsuario o en Reservas no se puede borrar
            if (FichaUsuario::where('user_id', $usuario->uuid)->first() || Reserva::where('user_id', $usuario->uuid)->first()) {
                $usuario->borrable = false;
            } else {
                $usuario->borrable = true;
            }
        }
        $roles = Role::orderBy('Name')->get();
        return view('usuarios.edit', compact('usuario'), compact('roles'));
    }
}
