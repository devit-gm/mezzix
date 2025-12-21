<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ siteName() }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito?v=281120252245" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ siteFavicon() }}-32x32.png?v=281120252245">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ siteFavicon() }}-16x16.png?v=281120252245">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="{{ route('manifest') }}">
    <meta name="theme-color" content="#a7380d">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="{{ siteName() }}">
    
    @php
        $domain = request()->getHost();
        $site = \App\Models\Site::where('dominio', $domain)->first();
        $basePath = $site && $site->carpeta_pwa ? '/' . trim($site->carpeta_pwa, '/') : '/images/icons';
    @endphp
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset($basePath . '/icon-192x192.png') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset($basePath . '/icon-152x152.png') }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ asset($basePath . '/icon-144x144.png') }}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ asset($basePath . '/icon-128x128.png') }}">
    
    <!-- iOS Splash Screens -->
    <!-- iPhone X, XS, 11 Pro, 12 mini, 13 mini (1125x2436) -->
    <link rel="apple-touch-startup-image" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)" href="{{ asset($basePath . '/splash-1125x2436.png') }}">
    <!-- iPhone XR, 11 (828x1792) -->
    <link rel="apple-touch-startup-image" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)" href="{{ asset($basePath . '/splash-828x1792.png') }}">
    <!-- iPhone XS Max, 11 Pro Max (1242x2688) -->
    <link rel="apple-touch-startup-image" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3)" href="{{ asset($basePath . '/splash-1242x2688.png') }}">
    <!-- iPhone 12, 12 Pro, 13, 13 Pro, 14 (1170x2532) -->
    <link rel="apple-touch-startup-image" media="(device-width: 390px) and (device-height: 844px) and (-webkit-device-pixel-ratio: 3)" href="{{ asset($basePath . '/splash-1170x2532.png') }}">
    <!-- iPhone 12 Pro Max, 13 Pro Max, 14 Plus (1284x2778) -->
    <link rel="apple-touch-startup-image" media="(device-width: 428px) and (device-height: 926px) and (-webkit-device-pixel-ratio: 3)" href="{{ asset($basePath . '/splash-1284x2778.png') }}">
    <!-- iPhone 14 Pro (1179x2556) -->
    <link rel="apple-touch-startup-image" media="(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3)" href="{{ asset($basePath . '/splash-1179x2556.png') }}">
    <!-- iPhone 14 Pro Max (1290x2796) -->
    <link rel="apple-touch-startup-image" media="(device-width: 430px) and (device-height: 932px) and (-webkit-device-pixel-ratio: 3)" href="{{ asset($basePath . '/splash-1290x2796.png') }}">
    <!-- iPad Mini, Air (1536x2048) -->
    <link rel="apple-touch-startup-image" media="(device-width: 768px) and (device-height: 1024px) and (-webkit-device-pixel-ratio: 2)" href="{{ asset($basePath . '/splash-1536x2048.png') }}">
    <!-- iPad Pro 10.5" (1668x2224) -->
    <link rel="apple-touch-startup-image" media="(device-width: 834px) and (device-height: 1112px) and (-webkit-device-pixel-ratio: 2)" href="{{ asset($basePath . '/splash-1668x2224.png') }}">
    <!-- iPad Pro 11" (1668x2388) -->
    <link rel="apple-touch-startup-image" media="(device-width: 834px) and (device-height: 1194px) and (-webkit-device-pixel-ratio: 2)" href="{{ asset($basePath . '/splash-1668x2388.png') }}">
    <!-- iPad Pro 12.9" (2048x2732) -->
    <link rel="apple-touch-startup-image" media="(device-width: 1024px) and (device-height: 1366px) and (-webkit-device-pixel-ratio: 2)" href="{{ asset($basePath . '/splash-2048x2732.png') }}">

    <!-- Scripts -->
    <link rel="stylesheet" href="{{ siteStyles() }}?v=281120252245">
    <script src="{{ asset('js/app.js') }}?v=281120252245" defer></script>
    
    @stack('styles')
    
    <style>
        /* Estilos para el grid de mesas */
        .mesas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .mesa-card {
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .mesa-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.2);
        }

        .mesa-card.libre {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .mesa-card.ocupada {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .mesa-card.cerrada {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .mesa-numero {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .mesa-descripcion {
            font-size: 1rem;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .mesa-info {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .mesa-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.3);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .mesa-precio {
            font-size: 1.5rem;
            font-weight: bold;
            margin-top: 10px;
        }

        /* Estilos para los botones de acción */
        .btn-mesa-action {
            margin: 5px;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: bold;
        }

        /* Estilos para el modal de resumen */
        .resumen-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .resumen-item:last-child {
            border-bottom: none;
        }

        .resumen-total {
            font-size: 1.5rem;
            font-weight: bold;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #333;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .mesas-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
                padding: 15px;
            }

            .mesa-numero {
                font-size: 2rem;
            }

            .mesa-precio {
                font-size: 1.2rem;
            }
        }

        /* Estilos adicionales para mejorar la UI */
        .main-content {
            min-height: calc(100vh - 240px);
            padding-bottom: 20px;
        }
        .main-content-cocinero {
            min-height: calc(100vh - 20px) !important;
            height: calc(100vh - 20px) !important;
            padding-bottom: 00px !important;
            overflow: hidden !important;
        }
        
        body {
            padding-bottom: 40px;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-figure {
            max-height: 40px;
            width: auto;
        }

        /* Estilos para el menú de usuario */
        .navbar-nav .nav-link {
            border-radius: 8px;
            margin: 0 5px;
            padding: 8px 15px;
            transition: all 0.3s ease;
        }

        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background-color: rgba(255, 255, 255, 0.1);
        }

        /* Mostrar flechas en dropdowns de menú (GESTIÓN, INFORMES) */
        .navbar-nav .dropdown-toggle::after {
            margin-left: 0.5em;
            vertical-align: 0.15em;
        }

        /* Ocultar flecha solo en el menú de usuario */
        .navbar-nav .dropdown:last-child .dropdown-toggle::after {
            display: none;
        }

        /* Estilo circular solo para el menú de usuario */
        .navbar-nav .dropdown:last-child #navbarDropdown {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            padding: 0;
        }

        /* Estilos para cards mejorados */
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            border-radius: 15px 15px 0 0 !important;
            font-weight: bold;
            padding: 15px 20px;
        }

        .card-footer {
            padding: 0.5rem 1rem;
        }

        /* Estilos para botones */
        

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Estilos para badges */
        .badge {
            border-radius: 8px;
            padding: 5px 10px;
            font-weight: 600;
        }

        /* Estilos para tablas */
        .table {
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th {
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
            transform: scale(1.01);
        }

        /* Estilos para formularios */
        .form-control,
        .form-select {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        /* Animaciones */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease;
        }

        /* Estilos para alertas */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Mejoras para el navbar brand */
        .navbar-brand {
            font-weight: bold;
            font-size: 1.3rem;
            transition: all 0.3s ease;
        }

        .navbar-brand:hover {
            transform: scale(1.05);
        }

        /* Estilos para dropdowns */
        .dropdown-menu {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 10px;
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 10px 15px;
            margin: 2px 0;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover,
        .dropdown-item.active {
            background-color: #dc3545;
            color: white;
        }

        .dropdown-divider {
            margin: 10px 0;
            border-top: 1px solid #e0e0e0;
        }

        /* Estilos para modales */
        .modal-content {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            border-radius: 15px 15px 0 0;
            border-bottom: 2px solid #e0e0e0;
            padding: 20px;
        }

        .modal-footer {
            border-top: 2px solid #e0e0e0;
            padding: 20px;
        }

        /* Progress bars */
        .progress {
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar {
            transition: width 0.6s ease;
        }

        /* Estilos para el footer */
        footer {
            margin-top: 0;
            margin-bottom: 0;
            padding: 0;
            background-color: #f8f9fa;
            border-radius: 15px 15px 0 0;
            min-height: auto;
            height: auto;
            position: relative;
        }
        
        footer .card-footer {
            min-height: 80px;
            height: auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-bottom: 0;
        }

        /* Estilos específicos para vista de mesas */
        .mesa-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .mesa-camarero {
            font-size: 0.9rem;
            margin-top: 5px;
            opacity: 0.9;
        }

        .mesa-tiempo {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        /* Ajustes responsive para navbar */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1rem;
            }

            .logo-figure {
                max-height: 30px;
            }

            .navbar-nav .nav-link {
                margin: 5px 0;
            }
        }



        /* Estilos adicionales para navbar brand mejorado */
        .brand-enhanced {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .brand-enhanced:hover {
            transform: scale(1.05);
            opacity: 0.9;
        }

        .brand-wrapper {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .brand-name {
            color: white;
            font-weight: 600;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
        }

        /* Mejorar el navbar en modo oscuro/rojo */
        .navbar.fondo-rojo {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar.fondo-rojo .navbar-brand {
            color: white !important;
        }

        .navbar.fondo-rojo .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
        }

        .navbar.fondo-rojo .nav-link:hover,
        .navbar.fondo-rojo .nav-link.active {
            color: white !important;
            background-color: rgba(255, 255, 255, 0.15);
        }

        .navbar.fondo-rojo .dropdown-menu {
            background-color: white;
        }

        .navbar.fondo-rojo .dropdown-item {
            color: #333;
        }

        .navbar.fondo-rojo .dropdown-item:hover,
        .navbar.fondo-rojo .dropdown-item.active {
            background-color: #dc3545;
            color: white;
        }

        /* Estilos para el icono del usuario en navbar - solo último elemento */
        .navbar.fondo-rojo .navbar-nav.ms-auto .nav-item:last-child #navbarDropdown {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            padding: 0;
            border: 2px solid rgba(255, 255, 255, 0.5);
            margin:5px;
        }

        .navbar.fondo-rojo .navbar-nav.ms-auto .nav-item:last-child #navbarDropdown:hover {
            background: rgba(255, 255, 255, 0.35);
            border-color: white;
        }

        /* Toggler mejorado */
        .navbar-toggler {
            border-color: rgba(255, 255, 255, 0.3);
            padding: 0.5rem 0.75rem;
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 0.9)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
    </style>
</head>

<body>

    @php
        $esCocineroEnCocina = auth()->check() && auth()->user()->role_id == \App\Enums\Role::COCINERO && request()->is('cocina/mesas');
    @endphp
    @guest
    <main class="py-3">
        @yield('content')
    </main>
    @else
    @if(!$esCocineroEnCocina)
    <nav class="navbar navbar-expand-md navbar-dark shadow-sm fondo-rojo">
        <div class="container-fluid px-2">
            @php
                try {
                    $ajustesLogo = \App\Models\Ajustes::first();
                    $logoUrl = ($ajustesLogo && $ajustesLogo->modo_operacion === 'mesas') ? url('/mesas') : url('/');
                } catch (\Exception $e) {
                    $logoUrl = url('/');
                }
            @endphp
            <a class="navbar-brand brand-enhanced" href="{{ $logoUrl }}">
                <div class="brand-wrapper">
                    <img src="{{ siteLogoNav() }}?v=281120252245" class="img-fluid logo-figure" alt="{{ siteName() }}">
                    <span class="brand-name">{{ siteName() }}</span>
                </div>
            </a>
            @php
                try {
                    $ajustesNav = \App\Models\Ajustes::first();
                    $modoOperacionNav = $ajustesNav->modo_operacion ?? 'fichas';
                } catch (\Exception $e) {
                    $modoOperacionNav = 'fichas';
                }
                $esUsuarioMesas = (Auth::user()->role_id == \App\Enums\Role::USUARIO_MESAS && $modoOperacionNav === 'mesas');
            @endphp
            
            @if($esUsuarioMesas)
                <!-- Usuario tipo 4 en modo mesas: botón directo a mesas -->
                <a href="{{ url('/mesas') }}" class="btn btn-outline-light ms-auto d-md-none">
                    <i class="bi bi-grid-3x3-gap"></i>
                </a>
            @else
                <!-- Menú normal con toggler -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>
            @endif

            @if(!$esUsuarioMesas)
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Left Side Of Navbar -->
                <ul class="navbar-nav me-auto ">
                    @if (app('site')->central == 0)
                    @php
                        try {
                            $modoOperacionMenu = ajustes_menu()->modo_operacion ?? 'fichas';
                        } catch (\Exception $e) {
                            $modoOperacionMenu = 'fichas';
                        }
                    @endphp
                    
                    @if($modoOperacionMenu === 'mesas')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('fichas.*') || request()->routeIs('mesas.*') || request()->routeIs('cocina.*') || Request::is('/') || Request::is('mesas') ? 'active' : '' }}" href="{{ url('/mesas') }}">
                                {{ __('Mesas') }}
                            </a>
                        </li>
                    @elseif($modoOperacionMenu === 'agencia_eventos')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('eventos.gestion.*') || request()->routeIs('eventos-publicos.*') || Request::is('/') ? 'active' : '' }}" href="{{ route('eventos.gestion.index') }}">
                                {{ __('Eventos') }}
                            </a>
                        </li>
                        
                                               
                    @else
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('fichas.*') || Request::is('/') ? 'active' : '' }}" href="{{ url('') }}">{{ __('Tokens') }}</a>
                        </li>
                    @endif
                    
                    @if (Auth::user()->role_id < \App\Enums\Role::USUARIO_MESAS)
                    @php
                        try {
                            $ajustesMenuGestion = ajustes_menu();
                            $modoOperacionMenuGestion = $ajustesMenuGestion->modo_operacion ?? 'fichas';
                        } catch (\Exception $e) {
                            $modoOperacionMenuGestion = 'fichas';
                        }
                    @endphp
                    @if($modoOperacionMenuGestion === 'agencia_eventos')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ url('/usuarios') }}">
                             {{ __('GESTIÓN USUARIOS') }}
                        </a>
                    </li>
                    @else
                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle {{ (request()->routeIs('usuarios.*') || request()->routeIs('familias.*') || request()->routeIs('productos.*') || request()->routeIs('servicios.*') || request()->routeIs('proveedores.*') || request()->routeIs('albaranes.*') || request()->routeIs('facturas.*')) ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ __('GESTIÓN') }}
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item {{ request()->routeIs('usuarios.*') ? 'active' : '' }}" href="{{ url('/usuarios') }}">{{ __('USUARIOS') }}</a>
                            <a class="dropdown-item {{ request()->routeIs('familias.*') ? 'active' : '' }}" href="{{ url('/familias') }}">{{ __('FAMILIAS') }}</a>
                            <a class="dropdown-item {{ request()->routeIs('productos.*') ? 'active' : '' }}" href="{{ url('/productos') }}">{{ __('PRODUCTOS') }}</a>
                            <a class="dropdown-item {{ request()->routeIs('servicios.*') ? 'active' : '' }}" href="{{ url('/servicios') }}">{{ __('SERVICIOS') }}</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item {{ request()->routeIs('proveedores.*') ? 'active' : '' }}" href="{{ url('/proveedores') }}">{{ __('PROVEEDORES') }}</a>
                            <a class="dropdown-item {{ request()->routeIs('albaranes.*') ? 'active' : '' }}" href="{{ url('/albaranes') }}">{{ __('ALBARANES') }}</a>
                            @php
                                try {
                                    $ajustesMenu = ajustes_menu();
                                    $modoOperacionMenu = $ajustesMenu->modo_operacion ?? 'fichas';
                                } catch (\Exception $e) {
                                    $modoOperacionMenu = 'fichas';
                                }
                            @endphp
                            @if($modoOperacionMenu === 'mesas')
                                <a class="dropdown-item {{ request()->routeIs('facturas.*') ? 'active' : '' }}" href="{{ url('/facturas') }}">{{ __('FACTURAS') }}</a>
                            @endif
                        </div>
                    </li>
                    @endif

                    @php
                        try {
                            $ajustesReservas = ajustes_menu();
                            $modoOperacionReservas = $ajustesReservas->modo_operacion ?? 'fichas';
                        } catch (\Exception $e) {
                            $modoOperacionReservas = 'fichas';
                        }
                    @endphp
                    @if($modoOperacionReservas !== 'agencia_eventos')
                    <li class="nav-item dropdown">
                        <a id="navbarDropdownReservas" class="nav-link dropdown-toggle {{ request()->routeIs('reservas.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ __('RESERVAS') }}
                        </a>

                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownReservas">
                            <a class="dropdown-item {{ request()->routeIs('reservas.index') || request()->routeIs('reservas.create') || request()->routeIs('reservas.edit') ? 'active' : '' }}" href="{{ url('/reservas') }}">{{ __('GESTIÓN') }}</a>
                            <a class="dropdown-item {{ request()->routeIs('reservas.calendario') ? 'active' : '' }}" href="{{ route('reservas.calendario') }}">{{ __('CALENDARIO') }}</a>
                        </div>
                    </li>
                    @endif
                    
                    @php
                        try {
                            $ajustesInformes = ajustes_menu();
                            $modoOperacionInformes = $ajustesInformes->modo_operacion ?? 'fichas';
                        } catch (\Exception $e) {
                            $modoOperacionInformes = 'fichas';
                        }
                    @endphp
                    @if($modoOperacionInformes !== 'agencia_eventos')
                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle {{ request()->routeIs('informes.*') || request()->routeIs('facturacion.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ __('INFORMES') }}
                        </a>

                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <a class="dropdown-item {{ request()->routeIs('facturacion.*') ? 'active' : '' }}" href="{{ url('/facturacion') }}">{{ __('FACTURACIÓN') }}</a>
                            
                            @php
                                try {
                                    $ajustes = ajustes_menu();
                                    $modoOperacion = $ajustes->modo_operacion ?? 'fichas';
                                } catch (\Exception $e) {
                                    $modoOperacion = 'fichas';
                                }
                            @endphp
                            
                            @if($modoOperacion !== 'mesas')
                                <a class="dropdown-item {{ request()->routeIs('informes.index') || request()->routeIs('informes.balance') ? 'active' : '' }}" href="{{ url('/informes') }}">{{ __('BALANCE POR SOCIO') }}</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item {{ request()->routeIs('informes.ventas-productos-fichas') ? 'active' : '' }}" href="{{ route('informes.ventas-productos-fichas') }}">{{ strtoupper(__('Ventas por Producto')) }}</a>
                                <a class="dropdown-item {{ request()->routeIs('informes.ventas-socios') ? 'active' : '' }}" href="{{ route('informes.ventas-socios') }}">{{ strtoupper(__('Ventas por Socio')) }}</a>
                                <a class="dropdown-item {{ request()->routeIs('informes.compras-usuarios') ? 'active' : '' }}" href="{{ route('informes.compras-usuarios') }}">{{ strtoupper(__('Compras por Usuario')) }}</a>
                                <a class="dropdown-item {{ request()->routeIs('informes.evolucion-temporal') ? 'active' : '' }}" href="{{ route('informes.evolucion-temporal') }}">{{ __('EVOLUCIÓN TEMPORAL') }}</a>
                            @else
                                <a class="dropdown-item {{ request()->routeIs('informes.ventas-productos') ? 'active' : '' }}" href="{{ route('informes.ventas-productos') }}">{{ strtoupper(__('Ventas por Producto')) }}</a>
                                <!-- <a class="dropdown-item {{ request()->routeIs('informes.ventas-camareros') ? 'active' : '' }}" href="{{ route('informes.ventas-camareros') }}">{{ strtoupper(__('Ventas por Camarero')) }}</a>
                                <a class="dropdown-item {{ request()->routeIs('informes.ocupacion-mesas') ? 'active' : '' }}" href="{{ route('informes.ocupacion-mesas') }}">{{ strtoupper(__('Ocupacion de Mesas')) }}</a>
                                <a class="dropdown-item {{ request()->routeIs('informes.horas-pico') ? 'active' : '' }}" href="{{ route('informes.horas-pico') }}">{{ strtoupper(__('Horas Pico')) }}</a> -->
                            @endif
                        </div>
                    </li>
                    @endif
                    
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('ajustes.*') ? 'active' : '' }}" href="{{ url('/ajustes') }}">{{ __('Settings') }}</a>
                    </li>
                    @endif

                            @else
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('sitios.*') ? 'active' : '' }}" href="{{ url('/sitios') }}">{{ __('SOCIEDADES') }}</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('licencias.*') ? 'active' : '' }}" href="{{ url('/licencias') }}">{{ __('Licencias') }}</a>
                            </li>
                            @endif
                </ul>

                <!-- Right Side Of Navbar -->
                <ul class="navbar-nav ms-auto" style="margin-right: 20px;">
                    <!-- Authentication Links -->
                    <li class="nav-item dropdown">
                        <a id="navbarDropdown" class="nav-link dropdown-toggle {{ request()->routeIs('usuarios.edit') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                            {{ getInitials(Auth::user()->name) }}
                        </a>

                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">

                            <a class="dropdown-item {{ request()->routeIs('usuarios.edit') ? 'active' : '' }}" href="{{ route('usuarios.edit', Auth::id()) }}">
                                {{ __('MI CUENTA') }}
                            </a>

                            <a class="dropdown-item {{ request()->routeIs('contacto.*') ? 'active' : '' }}" href="{{ route('contacto.index') }}">
                                {{ __('CONTACTO') }}
                            </a>

                            <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault();
                                                    document.getElementById('logout-form').submit();">
                                {{ __('CERRAR SESIÓN') }}
                            </a>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
            @endif
        </div>
    </nav>

    @endif
    <main class="py-3 main-content @if($esCocineroEnCocina) main-content-cocinero @endif">
        @yield('content')
        <form id="frmBorrar" action="" method="post">
            @csrf
            @method('DELETE')
        </form>
        <form id="frmEditar" action="" method="post">
            @csrf
            @method('PUT')
        </form>
    </main>
    @if(!$esCocineroEnCocina)
    <footer class="card">
        @yield('footer')
    </footer>
    @endif
    @endguest
        <!-- Service Worker Registration (solo en HTTPS) --> 
@if(request()->secure() || str_contains(request()->getHost(), '127.0.0.1'))        @php
            $domain = request()->getHost();
            $site = \App\Models\Site::where('dominio', $domain)->first();
            $iconBasePath = ($site && $site->carpeta_pwa) ? '/' . trim($site->carpeta_pwa, '/') : '/images/icons';
        @endphp
<script>
            // Variable global con la ruta base de iconos PWA
            window.PWA_ICON_PATH = '{{ $iconBasePath }}';
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/firebase-messaging-sw.js').then(registration => {
                        console.log('SW Firebase registrado:', registration.scope);
                        requestNotificationPermission();
                        verificarEstadoNotificaciones(); // Verificar al cargar
                    }).catch(error => console.log('Error SW:', error));
                });
            }
            
            // Función para verificar el estado de las notificaciones y mostrar/ocultar botón
            function verificarEstadoNotificaciones() {
                const botonActivar = document.getElementById('btnActivarNotificaciones');
                if (!botonActivar) return;
                
                if (!('Notification' in window)) {
                    botonActivar.style.display = 'none';
                    return;
                }
                
                const permission = Notification.permission;
                
                if (permission === 'default') {
                    // No ha dado permiso aún - mostrar botón
                    botonActivar.style.display = 'flex';
                } else {
                    // Ya ha dado permiso (granted) o lo ha bloqueado (denied) - ocultar botón
                    botonActivar.style.display = 'none';
                }
            }
            
            // Función para activar notificaciones desde el botón flotante
            async function activarNotificacionesDesdeBoton() {
                if (!('Notification' in window)) {
                    alert('Este dispositivo no soporta notificaciones');
                    return;
                }
                
                const permission = await Notification.requestPermission();
                
                if (permission === 'granted') {
                    await getFCMToken();
                    verificarEstadoNotificaciones(); // Ocultar botón
                    
                    // Mostrar notificación de confirmación
                    new Notification('¡Notificaciones activadas!', {
                        body: 'Recibirás notificaciones de actualizaciones y eventos importantes.',
                        icon: window.PWA_ICON_PATH + '/icon-192x192.png'
                    });
                } else if (permission === 'denied') {
                    alert('Has bloqueado las notificaciones. Ve a los ajustes de tu navegador para habilitarlas.');
                    verificarEstadoNotificaciones(); // Ocultar botón
                }
            }
            
            // Verificar periódicamente el estado (por si cambia desde ajustes del navegador)
            setInterval(verificarEstadoNotificaciones, 5000);
            
            async function requestNotificationPermission() {
            if (!('Notification' in window)) return;
            if (Notification.permission === 'granted') {
                await getFCMToken();
                return;
            }
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                await getFCMToken();
            }
        }
        async function getFCMToken() {
                const registration = await navigator.serviceWorker.getRegistration('/firebase-messaging-sw.js');
                const {
                    getMessaging,
                    getToken,
                    onMessage
                } = await import('https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging.js');
                const {
                    initializeApp
                } = await import('https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js');
                const app = initializeApp({
                    apiKey: "AIzaSyAKDn17J0jzjYrQFCGF7WRN6Lt4AW4n7PA",
                    authDomain: "go-mezzix.firebaseapp.com",
                    projectId: "go-mezzix",
                    storageBucket: "go-mezzix.firebasestorage.app",
                    messagingSenderId: "234995051320",
                    appId: "1:234995051320:web:f32c705f863362b936afcd"
                });
                const messaging = getMessaging(app);
                const token = await getToken(messaging, {
                    vapidKey: 'BBOSRefR0aGaqbaUf5i7VuTTAyfD7Rh9-v-6NPXBg-S48EhOkDojSO0RiE-UJ8D0KlhrwER44pwhZ8zBz0Chdfk',
                    serviceWorkerRegistration: registration
                });
                if (token) {
                    console.log("TOKEN:", token);
                    // Solo guardar si el usuario está autenticado
                    @auth
                    await saveTokenToServer(token);
                    @else
                    console.log("Usuario no autenticado, token no guardado");
                    @endauth
                } 
                
                // Recibir notificaciones en primer plano 
                onMessage(messaging, async (payload) => {
                    console.log("Mensaje en foreground:", payload);
                    const title = payload.data?.title ?? payload.notification?.title;
                    const body = payload.data?.body ?? payload.notification?.body;
                    const data = payload.data;
                    
                    // Si es iOS PWA → el SW debe mostrar la notificación 
                    const isIOS = /iPhone|iPad|iPod/.test(navigator.userAgent);
                    const isPWA = window.navigator.standalone === true;
                    
                    if (isIOS && isPWA) {
                        const reg = await navigator.serviceWorker.ready;
                        reg.showNotification(title, {
                            body: body,
                            icon: window.PWA_ICON_PATH + '/icon-192x192.png',
                            badge: window.PWA_ICON_PATH + '/icon-72x72.png',
                            data: data
                        });
                        return;
                    } 
                    
                    // Resto de navegadores → Notification API
                    if (Notification.permission === 'granted') {
                        const notification = new Notification(title, { 
                            body: body, 
                            icon: window.PWA_ICON_PATH + '/icon-192x192.png', 
                            badge: window.PWA_ICON_PATH + '/icon-72x72.png', 
                            data: data 
                        });
                        
                        // Manejar clic en la notificación
                        notification.onclick = function(event) {
                            event.preventDefault();
                            
                            // Si es notificación de evento, redirigir al detalle
                            if (data?.tipo === 'evento' && data?.evento_id) {
                                window.focus();
                                window.location.href = '/eventos-publicos/' + data.evento_id;
                            } else {
                                window.focus();
                            }
                            
                            notification.close();
                        };
                    } 
                });
                }
                async function saveTokenToServer(token) {
                    try {
                        const response = await fetch('/save-fcm-token', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            credentials: 'include',
                            body: JSON.stringify({ token })
                        });
                        
                        const data = await response.json();
                        if (response.ok) {
                            console.log('✅ Token FCM guardado correctamente:', data);
                        } else {
                            console.error('❌ Error al guardar token FCM:', data);
                        }
                    } catch (error) {
                        console.error('❌ Error al enviar token FCM:', error);
                    }
                } 
          </script> 
                @endif

    <!-- Botón flotante estilo WhatsApp - Solo para usuarios autenticados (excepto cocineros en vista cocina) -->
    @auth
    @php
        $ajustesChat = app('ajustes');
        $esUsuarioBasico = Auth::user()->role_id >= 4;
        $esAgenciaEventos = $ajustesChat->modo_operacion === 'agencia_eventos';
        $mostrarChat = !$esCocineroEnCocina && !($esAgenciaEventos && $esUsuarioBasico);
    @endphp
    @if (app('site')->central == 0 && $mostrarChat)
    <button id="btnNotificacionFlotante" class="btn-flotante-notificacion" data-bs-toggle="modal" data-bs-target="#modalNotificacion" title="Enviar notificación a todos">
        <i class="bi bi-chat-dots-fill"></i>
    </button>
    @endif
    
    <!-- Botón flotante para activar notificaciones (inferior derecha) -->
    @if((request()->secure() || str_contains(request()->getHost(), '127.0.0.1')) && !$esCocineroEnCocina)
    <button id="btnActivarNotificaciones" class="btn-flotante-activar-notificaciones" onclick="activarNotificacionesDesdeBoton()" title="Activar notificaciones push" style="display: none;">
        <i class="bi bi-bell-slash-fill"></i>
    </button>
    @endif
    @endauth

    <!-- Modal para enviar notificación -->
    <div class="modal fade" id="modalNotificacion" tabindex="-1" aria-labelledby="modalNotificacionLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalNotificacionLabel">
                        <i class="bi bi-chat-dots-fill"></i> Enviar Notificación
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formNotificacion">
                        <div class="mb-3">
                            <label for="mensajeNotificacion" class="form-label">Mensaje</label>
                            <textarea class="form-control" id="mensajeNotificacion" rows="4" placeholder="Escribe tu mensaje..." maxlength="200" required></textarea>
                            <small class="text-muted">Máximo 200 caracteres</small>
                        </div>
                        
                    </form>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x"></i>
                    </button>
                    <button type="button" class="btn btn-primary" onclick="enviarNotificacionGlobal()">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Modal z-index más alto */
        #modalNotificacion {
            z-index: 9999 !important;
        }
        
        #modalNotificacion .modal-backdrop {
            z-index: 9998 !important;
        }
        
        .modal-backdrop.show {
            z-index: -1 !important;
        }
        
        .btn-flotante-notificacion {
            position: fixed;
            bottom: 30px;
            left: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .btn-flotante-notificacion:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
            background: linear-gradient(135deg, #c82333 0%, #dc3545 100%);
        }
        
        .btn-flotante-notificacion:active {
            transform: scale(0.95);
        }
        
        /* Botón flotante para activar notificaciones (inferior derecha) */
        .btn-flotante-activar-notificaciones {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
            color: white;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 1000;
            animation: pulseNotification 2s infinite;
        }
        
        .btn-flotante-activar-notificaciones:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
            background: linear-gradient(135deg, #ff9800 0%, #ffc107 100%);
        }
        
        .btn-flotante-activar-notificaciones:active {
            transform: scale(0.95);
        }
        
        @keyframes pulseNotification {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        @media (max-width: 768px) {
            .btn-flotante-notificacion {
                bottom: 20px;
                left: 20px;
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
            
            .btn-flotante-activar-notificaciones {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }
    </style>

    <script>
        async function enviarNotificacionGlobal() {
            const mensaje = document.getElementById('mensajeNotificacion').value.trim();
            
            if (!mensaje) {
                alert('Por favor escribe un mensaje');
                return;
            }
            
            const botonEnviar = event.target;
            botonEnviar.disabled = true;
            botonEnviar.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            
            try {
                const response = await fetch('/enviar-notificacion-global', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ mensaje })
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    alert(`✅ Notificación enviada a ${data.enviadas} usuario(s)`);
                    document.getElementById('formNotificacion').reset();
                    // Cerrar modal simulando click en el botón de cerrar
                    document.querySelector('#modalNotificacion .btn-close').click();
                } else {
                    alert('❌ Error: ' + (data.error || 'No se pudo enviar'));
                }
            } catch (error) {
                console.error('Error al enviar notificación:', error);
                alert('❌ Error de conexión');
            } finally {
                botonEnviar.disabled = false;
                botonEnviar.innerHTML = '<i class="bi bi-send-fill"></i>';
            }
        }
    </script>

    <script>
        function triggerParentClick(event, tdElement) {
            event.stopPropagation();
            // Buscar el ancestro <tr> o .reserva-item
            let row = tdElement.closest('tr');
            if (!row) {
                row = tdElement.closest('.reserva-item');
            }
            if (row && row.dataset.borrable) {
                if (row.dataset.hrefborrar != null) {
                    if (confirm(row.dataset.textoborrar)) {
                        var formulario = document.getElementById("frmBorrar");
                        formulario.action = row.dataset.hrefborrar;
                        formulario.submit();
                    }
                }
            }
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js?v=281120252245"></script>
    
    <!-- Offline Manager para PWA -->
    <script src="{{ asset('js/offline-manager.js') }}?v={{ time() }}" defer></script>
    
    @stack('scripts')
</body>

</html>