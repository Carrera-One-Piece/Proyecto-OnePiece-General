{{-- Pantalla de acceso denegado. Laravel la muestra sola cuando algo hace abort(403). --}}
@extends('layouts.auth')

@section('titulo', 'Acceso denegado')
@section('tema', 'tema-cp')

@section('contenido')
    <div class="centro">
        <span class="sello">DENEGADO</span>
    </div>

    <h2>Acceso denegado</h2>
    <p class="subtitulo">Nivel de autorización insuficiente</p>

    <p>
        {{ $exception->getMessage() ?: 'No tiene permiso para entrar a esta sección.' }}
        Este intento quedó registrado en la bitácora de accesos.
    </p>

    <a class="boton" href="{{ auth()->check() ? route('inicio') : route('login') }}">Volver</a>
@endsection
