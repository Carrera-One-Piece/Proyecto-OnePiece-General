@extends('layouts.panel')

@section('titulo', 'Registrar reporte')

@section('contenido')
    <div class="inteligencia">
        @include('inteligencia.partes.menu')

        <h1>Registrar reporte de inteligencia</h1>

        <p class="descripcion">
            Registra la información recibida e identifica su fuente de origen.
            El informante es opcional; los demás campos son obligatorios.
        </p>

        {{-- Los errores de validación los muestra layouts.panel. --}}

        <form
            method="POST"
            action="{{ route('inteligencia.reportes.store') }}"
        >
            @csrf

            <label for="titulo">Título</label>
            <input
                id="titulo"
                name="Titulo"
                type="text"
                maxlength="150"
                value="{{ old('Titulo') }}"
                required
            >

            <label for="contenido">Contenido</label>
            <textarea
                id="contenido"
                name="Contenido"
                rows="8"
                required
            >{{ old('Contenido') }}</textarea>

            <label for="fuente">Fuente de origen</label>
            <input
                id="fuente"
                name="Fuente"
                type="text"
                maxlength="150"
                value="{{ old('Fuente') }}"
                placeholder="Ejemplo: Marina — Base de Marineford"
                aria-describedby="ayuda-fuente"
                required
            >

            <p id="ayuda-fuente" class="ayuda">
                Indica la organización o fuente que proporcionó la información:
                Marina, Cipher Pol, gobierno afiliado u otra fuente.
            </p>

            <label for="confiabilidad">Confiabilidad declarada</label>
            <select id="confiabilidad" name="Confiabilidad" required>
                <option value="">Selecciona una opción</option>

                @foreach (['Alta', 'Media', 'Baja'] as $nivel)
                    <option
                        value="{{ $nivel }}"
                        @selected(old('Confiabilidad') === $nivel)
                    >
                        {{ $nivel }}
                    </option>
                @endforeach
            </select>

            <label for="informante">Informante (opcional)</label>
            <select
                id="informante"
                name="ID_informante"
                aria-describedby="ayuda-informante"
            >
                <option value="">Sin informante identificado</option>

                @foreach ($informantes as $informante)
                    <option
                        value="{{ $informante->ID_personaje }}"
                        @selected(
                            (string) old('ID_informante')
                            === (string) $informante->ID_personaje
                        )
                    >
                        Personaje #{{ $informante->ID_personaje }}
                    </option>
                @endforeach
            </select>

            <p id="ayuda-informante" class="ayuda">
                Selecciona un personaje solo si está identificado como informante.
                La fecha se asignará automáticamente al guardar.
            </p>

            <div class="acciones">
                <button type="submit">Guardar reporte</button>

                <a href="{{ route('inteligencia.reportes.index') }}">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection