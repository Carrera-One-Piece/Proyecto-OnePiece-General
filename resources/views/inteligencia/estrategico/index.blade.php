@extends('layouts.panel')

@section('titulo', 'Reporte estratégico')

@section('contenido')
    <div class="inteligencia">
        @include('inteligencia.partes.menu')

        <h1>Reporte estratégico</h1>

        <p class="subtitulo">
            Información conjunta de tripulaciones, recompensas,
            navegación, eventos y amenazas.
        </p>

        <p class="ayuda">
            Generado: {{ $generadoEn->format('d/m/Y H:i:s') }}
            ({{ $generadoEn->timezoneName }}).
            Los datos se consultan al abrir o recargar esta página.
            Incluye registros históricos y no guarda una copia permanente.
        </p>

        <div class="resumen">
            <div class="indicador">
                <strong>{{ $tripulaciones->count() }}</strong>
                Tripulaciones registradas
            </div>

            <div class="indicador">
                <strong>{{ $recompensas->count() }}</strong>
                Registros de recompensas
            </div>

            <div class="indicador">
                <strong>{{ $movimientos->count() }}</strong>
                Posiciones registradas
            </div>

            <div class="indicador">
                <strong>{{ $eventos->count() }}</strong>
                Eventos registrados
            </div>

            <div class="indicador">
                <strong>{{ $amenazas->count() }}</strong>
                Análisis de amenazas
            </div>

            <div class="indicador">
                <strong>{{ $conexiones->count() }}</strong>
                Conexiones entre islas
            </div>
        </div>

        <nav class="navegacion" aria-label="Secciones del reporte">
            <a href="#tripulaciones">Tripulaciones</a>
            <a href="#recompensas">Recompensas</a>
            <a href="#movimientos">Posiciones de barcos</a>
            <a href="#eventos">Eventos</a>
            <a href="#amenazas">Amenazas</a>
            <a href="#conexiones">Conexiones entre islas</a>
        </nav>

        <section id="tripulaciones">
            <h2>Tripulaciones</h2>

            <div class="inteligencia-tabla"
                 role="region"
                 aria-label="Tripulaciones"
                 tabindex="0">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Nombre</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Recompensa general</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($tripulaciones as $fila)
                            <tr>
                                <td>#{{ $fila->ID_tripulacion }}</td>
                                <td><strong>{{ $fila->Nombre }}</strong></td>
                                <td>{{ $fila->Estado ?? 'Sin estado' }}</td>
                                <td class="numero">
                                    {{ $fila->Bounty_general !== null
                                        ? number_format((float) $fila->Bounty_general, 0, ',', '.')
                                        : 'Sin dato' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="vacio">
                                    No hay tripulaciones registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section id="recompensas">
            <h2>Recompensas</h2>

            <p class="ayuda">
                Incluye registros históricos y su estado.
                El conteo no equivale al número de piratas buscados.
            </p>

            <div class="inteligencia-tabla"
                 role="region"
                 aria-label="Recompensas"
                 tabindex="0">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Personaje</th>
                            <th scope="col">Valor</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Motivo</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($recompensas as $fila)
                            <tr>
                                <td>#{{ $fila->ID_recompensa }}</td>
                                <td>#{{ $fila->ID_personaje }}</td>
                                <td class="numero">
                                    {{ $fila->Valor !== null
                                        ? number_format((float) $fila->Valor, 0, ',', '.')
                                        : 'Sin dato' }}
                                </td>
                                <td>{{ $fila->Estado ?? 'Sin estado' }}</td>
                                <td class="inteligencia-fecha">
                                    {{ $fila->Fecha ?? 'Sin fecha' }}
                                </td>
                                <td class="inteligencia-contenido">
                                    {{ $fila->Motivo ?? 'Sin motivo registrado' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="vacio">
                                    No hay recompensas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section id="movimientos">
            <h2>Navegación: posiciones de barcos</h2>

            <p class="ayuda">
                Posiciones históricas registradas.
                No representan rutas calculadas ni predicciones.
            </p>

            <div class="inteligencia-tabla"
                 role="region"
                 aria-label="Posiciones de barcos"
                 tabindex="0">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Barco</th>
                            <th scope="col">Coordenada X</th>
                            <th scope="col">Coordenada Y</th>
                            <th scope="col">Fecha</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($movimientos as $fila)
                            <tr>
                                <td>#{{ $fila->ID_movimiento_barco }}</td>
                                <td>#{{ $fila->ID_barco }}</td>
                                <td>{{ $fila->Coordenada_X ?? 'Sin dato' }}</td>
                                <td>{{ $fila->Coordenada_Y ?? 'Sin dato' }}</td>
                                <td class="inteligencia-fecha">
                                    {{ $fila->Fecha_registro ?? 'Sin fecha' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="vacio">
                                    No hay posiciones de barcos registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section id="eventos">
            <h2>Eventos</h2>

            <div class="inteligencia-tabla"
                 role="region"
                 aria-label="Eventos"
                 tabindex="0">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Evento</th>
                            <th scope="col">Isla</th>
                            <th scope="col">Nivel de amenaza</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Descripción</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($eventos as $fila)
                            <tr>
                                <td>#{{ $fila->ID_evento }}</td>
                                <td><strong>{{ $fila->Nombre }}</strong></td>
                                <td>
                                    {{ $fila->ID_isla !== null
                                        ? '#' . $fila->ID_isla
                                        : 'Sin isla asociada' }}
                                </td>
                                <td>
                                    {{ $fila->Nivel_amenaza ?? 'Sin clasificar' }}
                                </td>
                                <td class="inteligencia-fecha">
                                    {{ $fila->Fecha_evento ?? 'Sin fecha' }}
                                </td>
                                <td class="inteligencia-contenido">
                                    <details class="inteligencia-detalle">
                                        <summary>Leer descripción</summary>
                                        <p class="inteligencia-texto">{{ $fila->Descripcion ?? 'Sin descripción' }}</p>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="vacio">
                                    No hay eventos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section id="amenazas">
            <h2>Análisis de amenazas</h2>

            <p class="ayuda">
                Evaluaciones almacenadas en la base de datos.
                Esta pantalla no calcula nuevos niveles de amenaza.
            </p>

            <div class="inteligencia-tabla"
                 role="region"
                 aria-label="Análisis de amenazas"
                 tabindex="0">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Personaje</th>
                            <th scope="col">Relación</th>
                            <th scope="col">Territorio</th>
                            <th scope="col">Responsable</th>
                            <th scope="col">Nivel</th>
                            <th scope="col">Fecha</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($amenazas as $fila)
                            <tr>
                                <td>#{{ $fila->ID_analisis }}</td>
                                <td>
                                    {{ $fila->ID_personaje !== null
                                        ? '#' . $fila->ID_personaje : '—' }}
                                </td>
                                <td>
                                    {{ $fila->ID_relacion !== null
                                        ? '#' . $fila->ID_relacion : '—' }}
                                </td>
                                <td>
                                    {{ $fila->ID_territorio !== null
                                        ? '#' . $fila->ID_territorio : '—' }}
                                </td>
                                <td>
                                    {{ $fila->Responsable ?? 'Sin responsable' }}
                                </td>
                                <td>
                                    {{ $fila->Nivel_amenaza ?? 'Sin clasificar' }}
                                </td>
                                <td class="inteligencia-fecha">
                                    {{ $fila->Fecha_analisis ?? 'Sin fecha' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="vacio">
                                    No hay análisis de amenazas registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section id="conexiones">
            <h2>Navegación: conexiones entre islas</h2>

            <p class="ayuda">
                Conexiones registradas entre islas de origen y destino.
                No indican recorridos realizados por una tripulación.
                Las unidades de distancia y tiempo están pendientes de confirmar.
            </p>

            <div class="inteligencia-tabla"
                 role="region"
                 aria-label="Conexiones entre islas"
                 tabindex="0">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Origen</th>
                            <th scope="col">Destino</th>
                            <th scope="col">Distancia registrada</th>
                            <th scope="col">Tiempo estimado registrado</th>
                            <th scope="col">Observaciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($conexiones as $conexion)
                            <tr>
                                <td>#{{ $conexion->ID_adyacencia }}</td>
                                <td>
                                    {{ $conexion->Isla_origen
                                        ?? 'Isla #' . $conexion->ID_isla_origen }}
                                </td>
                                <td>
                                    {{ $conexion->Isla_destino
                                        ?? 'Isla #' . $conexion->ID_isla_destino }}
                                </td>
                                <td>{{ $conexion->Distancia ?? 'Sin dato' }}</td>
                                <td>
                                    {{ $conexion->Tiempo_estimado ?? 'Sin dato' }}
                                </td>
                                <td class="inteligencia-contenido">
                                    {{ $conexion->Observaciones ?? 'Sin observaciones' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="vacio">
                                    No hay conexiones entre islas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection