{{--
    Marco de las pantallas de adentro del sistema (después de iniciar sesión):
    Recompensas, Administrar usuarios, Matriz de permisos y Bitácora.
    Login, registro e inicio siguen con layouts/auth.

    CSS: public/css/tema.css (colores del login) + panel.css (barra, hoja,
    tablas, botones) + recompensas.css (carteles de "SE BUSCA").

    El menú muestra cada sección solo si el rol tiene el permiso (@can).
    Esconder el enlace NO es la seguridad: cada ruta tiene su guardia.

    Cada pantalla define:
      @section('titulo')     -> texto de la pestaña del navegador
      @section('contenido')
--}}
@php($usuario = auth()->user())
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Panel') · Sistema de Inteligencia Gran Line</title>
    <link rel="stylesheet" href="{{ asset('css/tema.css') }}">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
    <link rel="stylesheet" href="{{ asset('css/recompensas.css') }}">
</head>
<body class="panel {{ $usuario->organizacion?->tema() ?? 'tema-marina' }}">

    <header class="barra">
        <a class="barra__logo" href="{{ route('inicio') }}">
            <img src="{{ asset('img/logo-gran-line.png') }}" alt="Sistema de Inteligencia Gran Line">
        </a>

        <nav class="barra__menu" aria-label="Menú principal">
            <a href="{{ route('inicio') }}" @class(['activo' => request()->routeIs('inicio')])>Inicio</a>
            @can('RECOMPENSAS.VER')
                <a href="{{ route('recompensas.index') }}" @class(['activo' => request()->routeIs('recompensas.*')])>Recompensas</a>
            @endcan
            @can('USUARIOS.VER')
                <a href="{{ route('usuarios.index') }}" @class(['activo' => request()->routeIs('usuarios.*')])>Usuarios</a>
                <a href="{{ route('roles.permisos') }}" @class(['activo' => request()->routeIs('roles.*')])>Permisos</a>
            @endcan
            @can('BITACORA.VER')
                <a href="{{ route('bitacora.index') }}" @class(['activo' => request()->routeIs('bitacora.*')])>Bitácora</a>
            @endcan
        </nav>

        <div class="barra__usuario">
            <span>{{ $usuario->Nombre }}</span>
            <small>{{ $usuario->rol?->Nombre_rol ?? 'Sin rol' }}</small>
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button type="submit" class="boton-chico boton-claro">Salir</button>
            </form>
        </div>
    </header>

    <main class="hoja">
        @if(session('aviso') || session('success'))
            <p class="aviso">{{ session('aviso') ?? session('success') }}</p>
        @endif

        @if(session('error'))
            <p class="error" role="alert">{{ session('error') }}</p>
        @endif

        @if($errors->any())
            <div class="error" role="alert">
                <ul>
                    @foreach($errors->all() as $mensaje)
                        <li>{{ $mensaje }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('contenido')
    </main>

    <p class="pie">Información clasificada · Uso exclusivo del personal autorizado</p>

    <script src="{{ asset('js/berries.js') }}"></script>
</body>
</html>
