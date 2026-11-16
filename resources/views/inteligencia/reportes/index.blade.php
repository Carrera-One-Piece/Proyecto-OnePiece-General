@extends('layouts.panel')

@section('titulo', 'Centro de Inteligencia Mundial')

@section('contenido')
    <section class="inteligencia">
        <div class="encabezado-seccion">
            <div>
                <h2>Centro de Inteligencia Mundial</h2>
                <p class="subtitulo">
                    Reportes recibidos y sus fuentes de información.
                </p>
            </div>

            @can('INFORMES.CREAR')
                <a
                    class="boton boton-chico"
                    href="{{ route('inteligencia.reportes.create') }}"
                >
                    + Registrar reporte
                </a>
            @endcan
        </div>

        <nav class="pestanas" aria-label="Secciones de inteligencia">
            <a
                class="activo"
                aria-current="page"
                href="{{ route('inteligencia.reportes.index') }}"
            >
                Reportes recibidos
            </a>

            <a href="{{ route('inteligencia.informes.index') }}">
                Informes consolidados
            </a>

            <a href="{{ route('inteligencia.estrategico.index') }}">
                Reporte estratégico
            </a>
        </nav>

        @if (session('exito'))
            <div class="aviso" role="status">
                {{ session('exito') }}
            </div>
        @endif

        <p class="resultados">
            @if ($reportes->total() === 0)
                Aún no hay reportes registrados.
            @else
                Mostrando {{ $reportes->firstItem() }}–{{ $reportes->lastItem() }}
                de {{ $reportes->total() }} reportes.
            @endif
        </p>

        <div
            class="inteligencia-tabla"
            role="region"
            aria-label="Reportes de inteligencia"
            tabindex="0"
        >
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Reporte</th>
                        <th scope="col">Fuente</th>
                        <th scope="col">Confiabilidad declarada</th>
                        <th scope="col">Fecha</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($reportes as $reporte)
                        <tr>
                            <td>#{{ $reporte->ID_reporte }}</td>

                            <td class="inteligencia-contenido">
                                <strong>{{ $reporte->Titulo }}</strong>

                                <details class="inteligencia-detalle">
                                    <summary>Leer contenido</summary>

                                    <p class="inteligencia-texto">{{ $reporte->Contenido }}</p>
                                </details>
                            </td>

                            <td>
                                {{ $reporte->Fuente ?: 'Sin fuente registrada' }}
                            </td>

                            <td>
                                @php
                                    $claseConfiabilidad = match ($reporte->Confiabilidad) {
                                        'Alta' => 'estado--ok',
                                        'Media' => 'estado--pendiente',
                                        'Baja' => 'estado--alerta',
                                        default => 'estado--neutro',
                                    };
                                @endphp

                                <span class="estado {{ $claseConfiabilidad }}">
                                    {{ $reporte->Confiabilidad ?: 'Sin clasificar' }}
                                </span>
                            </td>

                            <td class="inteligencia-fecha">
                                {{ $reporte->Fecha_reporte?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="vacio">
                                Registra un reporte para comenzar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $reportes->links('pagination::default') }}
    </section>
@endsection