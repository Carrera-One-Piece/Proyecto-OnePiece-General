@extends('layouts.auth')

@section('titulo', 'Solicitud en revisión')
@section('tema', 'tema-gobierno')

@section('contenido')
    <div class="centro">
        <span class="sello">PENDIENTE</span>
    </div>

    <h2>Su solicitud está pendiente de aprobación</h2>
    <p class="subtitulo">Expediente recibido y en revisión</p>

    <p>
        Su solicitud de alistamiento fue registrada correctamente. El Experto en
        Seguridad de la Información debe revisarla y asignarle un rol antes de que
        pueda ingresar. Hasta entonces, su cuenta permanecerá en estado
        <strong>Pendiente</strong>.
    </p>

    <a class="boton" href="{{ route('login') }}">Volver a Identificación</a>
@endsection
