{{--
    Mapa navegable de la Grand Line (RF10) + Módulo de Navegación (RF11-RF12).

    Todo lo dibuja public/js/mapa-navegacion.js con Leaflet, a partir de
    GET /mapa/datos. No necesita `npm run dev`: Leaflet viene del CDN y el
    script está en public/.

    Permisos:
      NAVEGACION.VER     -> ver el mapa, recorridos y predicciones guardadas
      NAVEGACION.EDITAR  -> pestaña "Ubicar" (mover tripulaciones y barcos)
      NAVEGACION.CREAR   -> botón "Predecir próximo movimiento"
--}}
@extends('layouts.panel')

@section('titulo', 'Mapa de navegación')

@push('estilos')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=IM+Fell+English:ital@0;1&family=Pirata+One&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
    <link rel="stylesheet" href="{{ asset('css/mapa-naval.css') }}?v=2">
@endpush

@section('contenido')
    <section
        class="carta"
        id="carta"
        data-datos="{{ route('mapa.datos') }}"
        data-ubicar="{{ url('/ubicaciones') }}"
        data-recorrido="{{ url('/navegacion/recorridos') }}"
        data-predicciones="{{ route('navegacion.predicciones') }}"
        data-predecir="{{ route('navegacion.predecir') }}"
        data-csrf="{{ csrf_token() }}"
        data-puede-editar="{{ $puedeEditar ? '1' : '0' }}"
        data-puede-predecir="{{ $puedePredecir ? '1' : '0' }}"
    >
        <header class="carta__cabecera">
            <div>
                <h2>Carta de navegación de la Grand Line</h2>
                <p class="subtitulo">Islas, posición de las tripulaciones, recorridos y rutas marítimas</p>
            </div>

            <div class="carta__herramientas">
                <label class="carta__buscar">
                    <span class="sr-only">Buscar isla o tripulación</span>
                    <input type="search" id="carta-buscar" list="carta-sugerencias"
                           placeholder="Buscar isla o tripulación…" autocomplete="off">
                    <datalist id="carta-sugerencias"></datalist>
                </label>

                <button type="button" class="boton-carta" id="carta-centrar" title="Ver todo el mapa">
                    ⌖ <span>Ver todo</span>
                </button>
                <button type="button" class="boton-carta boton-carta--oro" id="carta-expandir"
                        title="Expandir el mapa a pantalla completa" aria-pressed="false">
                    ⤢ <span>Expandir</span>
                </button>
            </div>
        </header>

        <div class="carta__cuerpo">
            <div class="carta__mapa-marco">
                <div id="mapa" class="carta__mapa" role="application" aria-label="Mapa de la Grand Line"></div>
                <p class="carta__cargando" id="carta-estado">Desplegando la carta náutica…</p>
            </div>

            <aside class="carta__panel" aria-label="Panel del mapa">
                <nav class="carta__pestanas" role="tablist">
                    <button type="button" role="tab" class="activa" data-pestana="tripulaciones">Tripulaciones</button>
                    <button type="button" role="tab" data-pestana="detalle">Detalle</button>
                    @if($puedeEditar)
                        <button type="button" role="tab" data-pestana="ubicar">Ubicar</button>
                    @endif
                </nav>

                <section class="carta__seccion activa" data-seccion="tripulaciones">
                    <ul class="lista-tripulaciones" id="lista-tripulaciones"></ul>
                </section>

                <section class="carta__seccion" data-seccion="detalle">
                    <div id="carta-detalle">
                        <p class="vacio">Haz clic en una isla o tripulación del mapa para ver su información.</p>
                    </div>
                </section>

                @if($puedeEditar)
                    <section class="carta__seccion" data-seccion="ubicar">
                        <form id="form-ubicar" class="form-ubicar" novalidate>
                            <p class="ayuda">Registra dónde está una tripulación o un barco. Elige una isla o haz clic en el mapa.</p>

                            <label>Tipo
                                <select name="tipo" id="ubicar-tipo">
                                    <option value="tripulacion">Tripulación</option>
                                    <option value="barco">Barco</option>
                                </select>
                            </label>

                            <label>Entidad
                                <select name="id" id="ubicar-id" required></select>
                            </label>

                            <label>Isla (opcional)
                                <select name="isla" id="ubicar-isla">
                                    <option value="">— En altamar (clic en el mapa) —</option>
                                </select>
                            </label>

                            <div class="form-ubicar__coords">
                                <label>X <input type="number" name="x" id="ubicar-x" min="0" max="1500" step="0.01" required></label>
                                <label>Y <input type="number" name="y" id="ubicar-y" min="0" max="1000" step="0.01" required></label>
                            </div>

                            <button type="button" class="boton-carta" id="ubicar-elegir" aria-pressed="false">
                                ⌖ Elegir el punto en el mapa
                            </button>

                            <div id="ubicar-mensaje" class="mensaje" role="status"></div>

                            <button type="submit" class="boton-carta boton-carta--oro">Guardar ubicación</button>
                        </form>
                    </section>
                @endif
            </aside>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <script src="{{ asset('js/mapa-navegacion.js') }}?v=2"></script>
@endpush
