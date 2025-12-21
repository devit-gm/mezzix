<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $ajustes = app('ajustes');
        
        // Redirigir según el modo de operación
        if ($ajustes && $ajustes->modo_operacion === 'agencia_eventos') {
            return redirect()->route('eventos-publicos.index');
        }
        
        return view('home');
    }
}
