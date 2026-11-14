<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte estratégico — Inteligencia</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #0c2037;
            color: #eef2f6;
            font-family: Arial, sans-serif;
        }

        main {
            max-width: 1300px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        a { color: #f5c568; }

        h1 { margin-top: 28px; }

        h2 { margin-bottom: 8px; }

        .ayuda {
            color: #b7c6d7;
            line-height: 1.6;
        }

        .navegacion {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin: 24px 0;
        }

        .resumen {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 14px;
            margin: 24px 0;
        }

        .indicador {
            padding: 20px;
            background: #142d47;
            border: 1px solid #35506c;
            border-radius: 8px;
        }

        .indicador strong {
            display: block;
            margin-bottom: 8px;
            color: #f5c568;
            font-size: 28px;
        }

        section {
            margin-top: 40px;
            scroll-margin-top: 20px;
        }

        .tabla {
            overflow-x: auto;
            background: #142d47;
            border: 1px solid #35506c;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 14px;
            border-bottom: 1px solid #35506c;
            vertical-align: top;
        }

        th { background: #1c3956; }

        .texto {
            min-width: 200px;
            max-width: 420px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .vacio {
            padding: 24px;
            color: #b7c6d7;
            text-align: center;
        }

        summary {
            cursor: pointer;
            color: #f5c568;
        }

        a:focus-visible, summary:focus-visible {
            outline: 3px solid #f5c568;
            outline-offset: 3px;
        }
    </style>
</head>
<body>
<main>
    <a href="{{ route('inteligencia.informes.index') }}">
        ← Volver a los informes
    </a>

    <h1>Reporte estratégico</h1>

    <p class="ayuda">
        Consulta conjunta de los registros disponibles en los cinco módulos.
        Incluye información histórica.
    </p>

    <p>
        Generado: {{ $generadoEn->format('d/m/Y H:i:s') }}
        ({{ $generadoEn->timezoneName }})
    </p>

    <p class="ayuda">
        Los datos se consultan al abrir o recargar esta página.
        Esta vista no guarda una copia permanente del reporte.
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
    </div>

    <nav class="navegacion" aria-label="Secciones del reporte">
        <a href="#tripulaciones">Tripulaciones</a>
        <a href="#recompensas">Recompensas</a>
        <a href="#movimientos">Navegación</a>
        <a href="#eventos">Eventos</a>
        <a href="#amenazas">Amenazas</a>
        <a href="#conexiones">Conexiones entre islas</a>
    </nav>

    <section id="tripulaciones">
        <h2>Tripulaciones</h2>

        <div class="tabla">
            <table>
                <caption class="ayuda" style="padding: 12px;">
                    Tripulaciones y recompensa general registrada.
                </caption>
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
                            <td>{{ $fila->ID_tripulacion }}</td>
                            <td>{{ $fila->Nombre }}</td>
                            <td>{{ $fila->Estado ?? 'Sin estado' }}</td>
                            <td>
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
            Se incluyen los registros históricos y su estado.
            El conteo no equivale al número de piratas buscados.
        </p>

        <div class="tabla">
            <table>
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
                            <td>{{ $fila->ID_recompensa }}</td>
                            <td>#{{ $fila->ID_personaje }}</td>
                            <td>
                                {{ $fila->Valor !== null
                                    ? number_format((float) $fila->Valor, 0, ',', '.')
                                    : 'Sin dato' }}
                            </td>
                            <td>{{ $fila->Estado ?? 'Sin estado' }}</td>
                            <td>{{ $fila->Fecha ?? 'Sin fecha' }}</td>
                            <td class="texto">{{ $fila->Motivo ?? 'Sin motivo registrado' }}</td>
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
            Estas son posiciones históricas registradas.
            No representan rutas calculadas ni predicciones.
        </p>

        <div class="tabla">
            <table>
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
                            <td>{{ $fila->ID_movimiento_barco }}</td>
                            <td>#{{ $fila->ID_barco }}</td>
                            <td>{{ $fila->Coordenada_X ?? 'Sin dato' }}</td>
                            <td>{{ $fila->Coordenada_Y ?? 'Sin dato' }}</td>
                            <td>{{ $fila->Fecha_registro ?? 'Sin fecha' }}</td>
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

        <div class="tabla">
            <table>
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
                            <td>{{ $fila->ID_evento }}</td>
                            <td>{{ $fila->Nombre }}</td>
                            <td>
                                {{ $fila->ID_isla !== null
                                    ? '#' . $fila->ID_isla
                                    : 'Sin isla asociada' }}
                            </td>
                            <td>{{ $fila->Nivel_amenaza ?? 'Sin clasificar' }}</td>
                            <td>{{ $fila->Fecha_evento ?? 'Sin fecha' }}</td>
                            <td>
                                <details>
                                    <summary>Leer descripción</summary>
                                    <p class="texto">{{ $fila->Descripcion ?? 'Sin descripción' }}</p>
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
            Se muestran evaluaciones ya almacenadas.
            Esta pantalla no calcula nuevos niveles de amenaza.
        </p>

        <div class="tabla">
            <table>
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
                            <td>{{ $fila->ID_analisis }}</td>
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
                            <td>{{ $fila->Responsable ?? 'Sin responsable' }}</td>
                            <td>{{ $fila->Nivel_amenaza ?? 'Sin clasificar' }}</td>
                            <td>{{ $fila->Fecha_analisis ?? 'Sin fecha' }}</td>
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

    <div class="tabla">
        <table>
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
                        <td>{{ $conexion->ID_adyacencia }}</td>

                        <td>
                            {{ $conexion->Isla_origen
                                ?? 'Isla #' . $conexion->ID_isla_origen }}
                        </td>

                        <td>
                            {{ $conexion->Isla_destino
                                ?? 'Isla #' . $conexion->ID_isla_destino }}
                        </td>

                        <td>{{ $conexion->Distancia ?? 'Sin dato' }}</td>

                        <td>{{ $conexion->Tiempo_estimado ?? 'Sin dato' }}</td>

                        <td class="texto">{{ $conexion->Observaciones ?? 'Sin observaciones' }}</td>
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
</main>
</body>
</html>