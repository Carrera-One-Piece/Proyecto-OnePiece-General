<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Informes consolidados</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #0c2037;
            color: #eef2f6;
            font-family: Arial, sans-serif;
        }

        main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        a { color: #f5c568; }

        header, .enlaces, .paginacion {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        header { margin: 24px 0; }

        h1 { margin: 0 0 8px; }

        .subtitulo { margin: 0; color: #b7c6d7; }

        .boton {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 6px;
            background: #f5c568;
            color: #172333;
            text-decoration: none;
            font-weight: bold;
        }

        .aviso {
            padding: 14px;
            margin-bottom: 20px;
            background: #163e32;
            border: 1px solid #5bb68b;
            border-radius: 6px;
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

        .titulo {
            min-width: 220px;
            overflow-wrap: anywhere;
        }

        .vacio { text-align: center; padding: 32px; }

        .paginacion { margin-top: 20px; }

        a:focus-visible {
            outline: 3px solid #f5c568;
            outline-offset: 3px;
        }
    </style>
</head>
<body>
<main>
    <nav class="enlaces" aria-label="Navegación de inteligencia">
        <a href="{{ route('inicio') }}">← Volver al inicio</a>

        <a href="{{ route('inteligencia.reportes.index') }}">
            Ver reportes recibidos
        </a>
        <a href="{{ route('inteligencia.estrategico.index') }}">
    Ver reporte estratégico
</a>
    </nav>

    <header>
        <div>
            <h1>Informes consolidados</h1>
            <p class="subtitulo">
                Análisis que reúne información de los reportes recibidos.
            </p>
        </div>

        @if (auth()->user()->rol?->permisos()
            ->where('Nombre_permiso', 'INFORMES.CREAR')->exists())
            <a class="boton"
               href="{{ route('inteligencia.informes.create') }}">
                Consolidar reportes
            </a>
        @endif
    </header>

    @if (session('exito'))
        <div class="aviso" role="status">
            {{ session('exito') }}
        </div>
    @endif

    <div class="tabla">
        <table>
            <caption style="padding: 14px; text-align: left;">
                {{ $informes->total() }} informes registrados
            </caption>

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
                        <td>{{ $informe->ID_informe }}</td>

                        <td class="titulo">
                            <strong>{{ $informe->Titulo }}</strong>
                        </td>

                        <td>{{ $informe->Autor ?: 'Sin autor registrado' }}</td>

                        <td>{{ $informe->reportes_count }}</td>

                        <td>
                            {{ $informe->Fecha_consolidacion?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                        </td>

                        <td>
                            <a href="{{ route('inteligencia.informes.show', $informe->ID_informe) }}">
                                Ver detalle
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="vacio">
                            Aún no hay informes consolidados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($informes->hasPages())
        <nav class="paginacion" aria-label="Páginas de informes">
            <div>
                @if ($informes->previousPageUrl())
                    <a href="{{ $informes->previousPageUrl() }}">← Anterior</a>
                @endif
            </div>

            <span>
                Página {{ $informes->currentPage() }}
                de {{ $informes->lastPage() }}
            </span>

            <div>
                @if ($informes->nextPageUrl())
                    <a href="{{ $informes->nextPageUrl() }}">Siguiente →</a>
                @endif
            </div>
        </nav>
    @endif
</main>
</body>
</html>