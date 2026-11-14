<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $informe->Titulo }} — Inteligencia</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #0c2037;
            color: #eef2f6;
            font-family: Arial, sans-serif;
        }

        main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        a { color: #f5c568; }

        h1 {
            margin-top: 28px;
            overflow-wrap: anywhere;
        }

        h2 { margin-top: 32px; }

        .panel {
            padding: 24px;
            margin-top: 20px;
            background: #142d47;
            border: 1px solid #35506c;
            border-radius: 8px;
        }

        .metadatos {
            display: flex;
            flex-wrap: wrap;
            gap: 12px 32px;
            color: #b7c6d7;
        }

        .texto {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        .reporte {
            padding: 20px;
            margin-top: 16px;
            background: #142d47;
            border: 1px solid #35506c;
            border-radius: 8px;
        }

        .reporte h3 {
            margin-top: 0;
            color: #f5c568;
            overflow-wrap: anywhere;
        }

        .aviso {
            padding: 14px;
            margin-top: 20px;
            background: #163e32;
            border: 1px solid #5bb68b;
            border-radius: 6px;
        }

        a:focus-visible {
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
        <h2 id="titulo-resumen" style="margin-top: 0;">
            Resumen ejecutivo
        </h2>

        <p class="texto">{{ $informe->Resumen_ejecutivo ?: 'Este informe no tiene un resumen registrado.' }}</p>
    </section>

    <section aria-labelledby="titulo-reportes">
        <h2 id="titulo-reportes">
            Reportes que sustentan el informe
            ({{ $informe->reportes->count() }})
        </h2>

        @forelse ($informe->reportes as $reporte)
            <article class="reporte">
                <h3>
                    #{{ $reporte->ID_reporte }} — {{ $reporte->Titulo }}
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
            <p class="panel">
                Este informe no tiene reportes vinculados.
            </p>
        @endforelse
    </section>
</main>
</body>
</html>