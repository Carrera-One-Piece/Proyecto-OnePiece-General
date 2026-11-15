@extends('layouts.panel')

@section('titulo', 'Informes consolidados')

@section('contenido')
    <div class="inteligencia">
        @include('inteligencia.partes.menu')

        <div class="encabezado-seccion">
            <div>
                <h2>Informes consolidados</h2>
                <p class="subtitulo">
                    Análisis que reúne información de los reportes recibidos.
                </p>
            </div>

            @can('INFORMES.CREAR')
                <a
                    class="boton boton-chico"
                    href="{{ route('inteligencia.informes.create') }}"
                >
                    + Consolidar reportes
                </a>
            @endcan
        </div>

        @if (session('exito'))
            <div class="aviso" role="status">
                {{ session('exito') }}
            </div>
        @endif

        <p class="resultados">
            @if ($informes->total() === 0)
                Aún no hay informes consolidados.
            @else
                Mostrando {{ $informes->firstItem() }}–{{ $informes->lastItem() }}
                de {{ $informes->total() }} informes.
            @endif
        </p>

        <div
            class="inteligencia-tabla"
            role="region"
            aria-label="Informes consolidados"
            tabindex="0"
        >
            <table class="tabla">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Informe</th>
                        <th scope="col">Autor</th>
                        <th scope="col">Reportes vinculados</th>
                        <th scope="col">Fecha</th>
                        <th scope="col">Acción</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($informes as $informe)
                        <tr>
                            <td>#{{ $informe->ID_informe }}</td>

                            <td class="inteligencia-contenido">
                                <strong>{{ $informe->Titulo }}</strong>
                            </td>

                            <td>
                                {{ $informe->Autor ?: 'Sin autor registrado' }}
                            </td>

                            <td>
                                <span class="contador">
                                    {{ $informe->reportes_count }}
                                </span>
                            </td>

                            <td class="inteligencia-fecha">
                                {{ $informe->Fecha_consolidacion?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                            </td>

                            <td>
                                <a
                                    class="boton boton-chico boton-claro"
                                    href="{{ route('inteligencia.informes.show', $informe->ID_informe) }}"
                                    aria-label="Ver informe: {{ $informe->Titulo }}"
                                >
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="vacio">
                                Consolida reportes para crear el primer informe.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $informes->links('pagination::default') }}
    </div>
@endsection