@extends('layouts.panel')

@section('titulo', 'Consolidar reportes')

@section('contenido')
    <div class="inteligencia">
        @include('inteligencia.partes.menu')

        <h1>Consolidar reportes</h1>

        <p class="descripcion">
            Selecciona al menos dos reportes relacionados y redacta un resumen
            que reúna sus hallazgos, evite repetir información y señale
            posibles contradicciones entre las fuentes.
        </p>

        @php
            $seleccionados = array_map(
                'strval',
                (array) old('reportes', [])
            );
        @endphp

        <form
            method="POST"
            action="{{ route('inteligencia.informes.store') }}"
        >
            @csrf

            <label class="campo" for="titulo">Título del informe</label>
            <input
                id="titulo"
                name="Titulo"
                type="text"
                maxlength="150"
                value="{{ old('Titulo') }}"
                required
            >

            <fieldset>
                <legend>Reportes de origen</legend>

                <p class="ayuda">
                    Lee el contenido antes de seleccionar los reportes.
                    Los registros originales conservarán sus datos.
                </p>

                @forelse ($reportes as $reporte)
                    <article class="reporte">
                        <label
                            class="seleccion"
                            for="reporte-{{ $reporte->ID_reporte }}"
                        >
                            <input
                                id="reporte-{{ $reporte->ID_reporte }}"
                                type="checkbox"
                                name="reportes[]"
                                value="{{ $reporte->ID_reporte }}"
                                @checked(in_array(
                                    (string) $reporte->ID_reporte,
                                    $seleccionados,
                                    true
                                ))
                            >

                            <span>
                                #{{ $reporte->ID_reporte }}
                                — {{ $reporte->Titulo }}
                            </span>
                        </label>

                        <div class="metadatos">
                            <span>
                                Fuente:
                                {{ $reporte->Fuente ?: 'Sin fuente registrada' }}
                            </span>

                            <span>
                                Confiabilidad:
                                {{ $reporte->Confiabilidad ?: 'Sin clasificar' }}
                            </span>

                            <span>
                                Fecha:
                                {{ $reporte->Fecha_reporte?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                            </span>
                        </div>

                        <details class="inteligencia-detalle">
                            <summary>Leer contenido</summary>
                            <p class="inteligencia-texto">{{ $reporte->Contenido }}</p>
                        </details>
                    </article>
                @empty
                    <p class="vacio">
                        No hay reportes disponibles para consolidar.
                    </p>
                @endforelse
            </fieldset>

            <label class="campo" for="resumen">Resumen ejecutivo</label>
            <textarea
                id="resumen"
                name="Resumen_ejecutivo"
                rows="8"
                aria-describedby="ayuda-resumen"
                required
            >{{ old('Resumen_ejecutivo') }}</textarea>

            <p id="ayuda-resumen" class="ayuda">
                Describe los hechos principales, las coincidencias,
                las diferencias y las conclusiones sustentadas por los reportes.
                El autor y la fecha se asignarán automáticamente.
            </p>

            @if ($reportes->count() < 2)
                <p class="aviso" role="status">
                    Debes registrar al menos dos reportes antes de consolidar.
                </p>
            @endif

            <div class="acciones">
                <button
                    type="submit"
                    @disabled($reportes->count() < 2)
                >
                    Guardar informe consolidado
                </button>

                <a href="{{ route('inteligencia.informes.index') }}">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
@endsection