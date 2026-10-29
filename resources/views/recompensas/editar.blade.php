{{--
    Corregir el motivo o el evento de una recompensa (rebanada B).
    El valor NO se cambia aquí: para eso está "Aumentar", así queda en el historial.
--}}
@extends('layouts.panel')

@use('App\Models\Recompensa')

@section('titulo', 'Corregir recompensa')

@section('contenido')
    <h2>Corregir recompensa</h2>
    <p class="subtitulo">
        {{ $recompensa->personaje?->Nombre ?? 'Pirata desconocido' }} ·
        {{ Recompensa::berries($recompensa->Valor) }} ·
        <span class="estado {{ $recompensa->claseEstado() }}">{{ $recompensa->Estado }}</span>
    </p>

    <form class="formulario" method="post" action="{{ route('recompensas.actualizar', $recompensa->ID_recompensa) }}">
        @csrf
        @method('PUT')

        <label for="ID_evento">Evento que la causó</label>
        <select id="ID_evento" name="ID_evento">
            <option value="">— Ninguno —</option>
            @foreach($eventos as $evento)
                <option value="{{ $evento->ID_evento }}" @selected(old('ID_evento', $recompensa->ID_evento) == $evento->ID_evento)>{{ $evento->Nombre }}</option>
            @endforeach
        </select>

        <label for="Motivo">Motivo</label>
        <textarea id="Motivo" name="Motivo" maxlength="1000" required>{{ old('Motivo', $recompensa->Motivo) }}</textarea>
        <p class="ayuda">Para subir el valor use "Aumentar" en la ficha del pirata.</p>

        <div class="botones">
            <a class="boton boton-claro" href="{{ route('recompensas.pirata', $recompensa->ID_personaje) }}">Volver</a>
            <button type="submit">Guardar cambios</button>
        </div>
    </form>
@endsection
