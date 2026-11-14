<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Centro de Inteligencia Mundial</title>

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

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin: 24px 0;
        }

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

        .contenido {
            min-width: 240px;
            max-width: 440px;
            overflow-wrap: anywhere;
        }

        summary {
            cursor: pointer;
            color: #f5c568;
            margin-top: 8px;
        }

        .texto {
            white-space: pre-wrap;
            line-height: 1.5;
        }

        .vacio { text-align: center; padding: 32px; }

        .paginacion {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<main>
    <a href="{{ route('inicio') }}">← Volver al inicio</a>
<p>
    <a href="{{ route('inteligencia.informes.index') }}">
        Ver informes consolidados
    </a>
</p>
    <header>
        <div>
            <h1>Centro de Inteligencia Mundial</h1>
            <p class="subtitulo">
                Reportes recibidos y sus fuentes de información.
            </p>
        </div>

        {{-- Usa el mismo permiso que protege la ruta de creación. --}}
        @if (auth()->user()->rol?->permisos()
            ->where('Nombre_permiso', 'INFORMES.CREAR')->exists())
            <a class="boton"
               href="{{ route('inteligencia.reportes.create') }}">
                Registrar reporte
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
                {{ $reportes->total() }} reportes registrados
            </caption>

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
                        <td>{{ $reporte->ID_reporte }}</td>

                        <td class="contenido">
                            <strong>{{ $reporte->Titulo }}</strong>

                            <details>
                                <summary>Leer contenido</summary>
                                <p class="texto">{{ $reporte->Contenido }}</p>
                            </details>
                        </td>

                        <td>{{ $reporte->Fuente ?: 'Sin fuente registrada' }}</td>

                        <td>{{ $reporte->Confiabilidad ?: 'Sin clasificar' }}</td>

                        <td>
                            {{ $reporte->Fecha_reporte?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="vacio">
                            Aún no hay reportes registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($reportes->hasPages())
        <nav class="paginacion" aria-label="Páginas de reportes">
            <div>
                @if ($reportes->previousPageUrl())
                    <a href="{{ $reportes->previousPageUrl() }}">← Anterior</a>
                @endif
            </div>

            <span>
                Página {{ $reportes->currentPage() }}
                de {{ $reportes->lastPage() }}
            </span>

            <div>
                @if ($reportes->nextPageUrl())
                    <a href="{{ $reportes->nextPageUrl() }}">Siguiente →</a>
                @endif
            </div>
        </nav>
    @endif
</main>
</body>
</html>