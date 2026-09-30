{{--
    Pantalla a la que se llega después de iniciar sesión.
    Los enlaces se muestran según los permisos del rol (@can). Ocultar un
    enlace NO protege nada: la protección real está en las rutas.
--}}
@extends('layouts.auth')

@php($usuario = auth()->user())

@section('titulo', 'Panel del personal')
@section('tema', $usuario->organizacion?->tema() ?? 'tema-marina')

@section('contenido')
    <h2>Bienvenido, {{ $usuario->Nombre }}</h2>
    <p class="subtitulo">Identificación verificada</p>

    <p class="centro">
        <span class="insignia">{{ $usuario->organizacion?->Nombre ?? 'Sin organización' }}</span>
    </p>

    <p class="centro">
        {{ $usuario->Correo }}<br>
        Rol: <strong>{{ $usuario->rol?->Nombre_rol ?? 'Sin rol' }}</strong>
    </p>

    <ul class="menu">
        <li><a href="{{ url('/mapa') }}">Mapa de navegación</a></li>
        @can('USUARIOS.VER')
            <li><a href="{{ route('usuarios.index') }}">Administrar usuarios</a></li>
            <li><a href="{{ route('roles.permisos') }}">Matriz de permisos</a></li>
        @endcan
        @can('BITACORA.VER')
            <li><a href="{{ route('bitacora.index') }}">Bitácora de accesos</a></li>
        @endcan
    </ul>

    <form action="{{ route('logout') }}" method="post">
        @csrf
        <button type="submit">Cerrar sesión</button>
    </form>
@endsection
