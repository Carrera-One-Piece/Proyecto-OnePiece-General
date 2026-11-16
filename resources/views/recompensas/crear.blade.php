{{-- Formulario para emitir una recompensa nueva (rebanada B). La fecha se pone sola. --}}
@extends('layouts.panel')

@section('titulo', 'Emitir recompensa')

@section('contenido')
    <h2>Emitir recompensa</h2>
    <p class="subtitulo">Cada pirata puede tener una sola recompensa activa. Si ya tiene una, auméntela desde su ficha.</p>

    <form class="formulario" method="post" action="{{ route('recompensas.guardar') }}">
        @csrf

        <label for="ID_personaje">Pirata</label>
        <select id="ID_personaje" name="ID_personaje" required>
            <option value="">— Escoja un pirata —</option>
            @foreach($personajes as $pirata)
                @php($yaTiene = in_array($pirata->ID_personaje, $conActiva))
                <option value="{{ $pirata->ID_personaje }}" @disabled($yaTiene)
                        @selected(! $yaTiene && old('ID_personaje', $elegido) == $pirata->ID_personaje)>
                    {{ $pirata->Nombre }}{{ $pirata->Alias ? ' «'.$pirata->Alias.'»' : '' }}{{ $yaTiene ? ' — ya tiene recompensa activa' : '' }}
                </option>
            @endforeach
        </select>
        @if(count(array_unique($conActiva)) >= $personajes->count())
            <p class="ayuda">Todos los piratas registrados ya tienen una recompensa activa.</p>
        @endif

        <label for="Valor">Valor (berries)</label>
        <input type="text" inputmode="numeric" data-berries id="Valor" name="Valor" value="{{ old('Valor') }}"
               placeholder="Ej.: 1.000.000" required>

        <label for="ID_evento">Evento que la causó (opcional)</label>
        <select id="ID_evento" name="ID_evento">
            <option value="">— Ninguno —</option>
            @foreach($eventos as $evento)
                <option value="{{ $evento->ID_evento }}" @selected(old('ID_evento') == $evento->ID_evento)>{{ $evento->Nombre }}</option>
            @endforeach
        </select>

        <label for="Motivo">Motivo</label>
        <textarea id="Motivo" name="Motivo" maxlength="1000" required>{{ old('Motivo') }}</textarea>

        <div class="botones">
            <a class="boton boton-claro" href="{{ route('recompensas.index') }}">Volver</a>
            <button type="submit">Emitir recompensa</button>
        </div>
    </form>
@endsection
