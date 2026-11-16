@extends('layouts.auth')

@section('interfaz', 'interfaz-naval')

@php($usuario = auth()->user())

@section('titulo', 'Panel del personal')
@section('tema', $usuario->organizacion?->tema() ?? 'tema-marina')

@section('contenido')
    <h2>Bienvenido, {{ $usuario->Nombre }}</h2>

    <p class="subtitulo">Identificación verificada</p>

    <p class="centro">
        <span class="insignia">
            {{ $usuario->organizacion?->Nombre ?? 'Sin organización' }}
        </span>
    </p>

    <p class="centro">
        {{ $usuario->Correo }}<br>
        Rol:
        <strong>
            {{ $usuario->rol?->Nombre_rol ?? 'Sin rol' }}
        </strong>
    </p>

    <ul class="menu">
        @can('NAVEGACION.VER')
            <li>
                <a href="{{ route('mapa') }}">
                    Mapa de navegación
                </a>
            </li>
        @endcan

        @can('RECOMPENSAS.VER')
            <li>
                <a href="{{ route('recompensas.index') }}">
                    Tablón de recompensas
                </a>
            </li>
        @endcan

        @can('USUARIOS.VER')
            <li>
                <a href="{{ route('usuarios.index') }}">
                    Administrar usuarios
                </a>
            </li>

            <li>
                <a href="{{ route('roles.permisos') }}">
                    Matriz de permisos
                </a>
            </li>
        @endcan

        @can('BITACORA.VER')
            <li>
                <a href="{{ route('bitacora.index') }}">
                    Bitácora de accesos
                </a>
            </li>
        @endcan

        @can('INFORMES.VER')
            <li>
                <a href="{{ route('inteligencia.reportes.index') }}">
                    Centro de Inteligencia Mundial
                </a>
            </li>
        @endcan
    </ul>

    <form action="{{ route('logout') }}" method="post">
        @csrf

        <button type="submit" class="boton-salir">
            Cerrar sesión
        </button>
    </form>
@endsection