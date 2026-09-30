@extends('layouts.auth')

@section('titulo', 'Identificación')

@section('contenido')
    <h2>Identificación del Personal</h2>
    <p class="subtitulo">Presente sus credenciales para acceder al sistema</p>

    @if (session('aviso'))
        <div class="aviso" role="status">{{ session('aviso') }}</div>
    @endif

    @if ($errors->any())
        <div class="error" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- method="post": la contraseña nunca debe ir en la URL. --}}
    <form action="{{ route('login') }}" method="post" autocomplete="on">
        @csrf

        <label for="Correo">Correo institucional</label>
        <input type="email" id="Correo" name="Correo" required autofocus
               value="{{ old('Correo') }}" placeholder="nombre@organizacion.gov">

        <label for="Contrasena">Clave de acceso</label>
        <input type="password" id="Contrasena" name="Contrasena" required placeholder="••••••••">

        <button type="submit">Ingresar</button>
    </form>

    <p class="enlace-extra">
        ¿Aún sin credenciales?
        <a href="{{ route('registro') }}">Solicitar alistamiento</a>
    </p>
@endsection
