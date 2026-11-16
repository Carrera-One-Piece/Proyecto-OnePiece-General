@extends('layouts.panel')

@section('titulo', $informe->Titulo)

@section('contenido')
    <div class="inteligencia">
        @include('inteligencia.partes.menu')

        <p>
            <a href="{{ route('inteligencia.informes.index') }}">
                ← Volver a los informes
            </a>
        </p>

        @if (session('exito'))
            <div class="aviso" role="status">
                {{ session('exito') }}
            </div>
        @endif

        <h1>{{ $informe->Titulo }}</h1>

        <div class="metadatos">
            <span>Informe #{{ $informe->ID_informe }}</span>

            <span>
                Autor: {{ $informe->Autor ?: 'Sin autor registrado' }}
            </span>

            <span>
                Fecha:
                {{ $informe->Fecha_consolidacion?->format('d/m/Y H:i') ?? 'Sin fecha' }}
            </span>
        </div>

        <section class="panel" aria-labelledby="titulo-resumen">
            <h2 id="titulo-resumen">Resumen ejecutivo</h2>

            <p class="texto">{{ $informe->Resumen_ejecutivo ?: 'Este informe no tiene un resumen registrado.' }}</p>
        </section>

        <section aria-labelledby="titulo-reportes">
            <h2 id="titulo-reportes">
                Reportes que sustentan el informe
                ({{ $informe->reportes->count() }})
            </h2>

            <p class="ayuda">
                Fuentes originales vinculadas al análisis consolidado.
            </p>

            @forelse ($informe->reportes as $reporte)
                <article class="reporte">
                    <h3>
                        #{{ $reporte->ID_reporte }}
                        — {{ $reporte->Titulo }}
                    </h3>

                    <div class="metadatos">
                        <span>
                            Fuente:
                            {{ $reporte->Fuente ?: 'Sin fuente registrada' }}
                        </span>

                        <span>
                            Confiabilidad declarada:
                            {{ $reporte->Confiabilidad ?: 'Sin clasificar' }}
                        </span>

                        <span>
                            Fecha:
                            {{ $reporte->Fecha_reporte?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                        </span>
                    </div>

                    <p class="texto">{{ $reporte->Contenido }}</p>
                </article>
            @empty
                <p class="vacio">
                    Este informe no tiene reportes vinculados.
                </p>
            @endforelse
        </section>
    </div>
@endsection