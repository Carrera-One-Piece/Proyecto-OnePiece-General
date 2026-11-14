<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consolidar reportes</title>

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

        h1 { margin-top: 28px; }

        .ayuda {
            color: #b7c6d7;
            line-height: 1.5;
        }

        .campo {
            display: block;
            margin: 24px 0 8px;
            font-weight: bold;
        }

        input[type="text"], textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #69839e;
            border-radius: 5px;
            background: #f6f8fb;
            color: #172333;
            font: inherit;
        }

        textarea { resize: vertical; }

        fieldset {
            min-width: 0;
            padding: 20px;
            margin: 28px 0;
            border: 1px solid #35506c;
            border-radius: 8px;
        }

        legend {
            padding: 0 8px;
            font-size: 20px;
            font-weight: bold;
        }

        .reporte {
            padding: 16px;
            margin-top: 14px;
            background: #142d47;
            border: 1px solid #35506c;
            border-radius: 6px;
        }

        .seleccion {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            cursor: pointer;
            font-weight: bold;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        input[type="checkbox"] {
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            accent-color: #f5c568;
        }

        .metadatos {
            color: #b7c6d7;
            font-size: 14px;
            line-height: 1.6;
        }

        summary {
            cursor: pointer;
            color: #f5c568;
            margin-top: 12px;
        }

        .contenido {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            line-height: 1.6;
        }

        .errores {
            padding: 16px;
            background: #532631;
            border: 1px solid #ec9ba9;
            border-radius: 6px;
        }

        .acciones {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        button {
            padding: 12px 18px;
            border: 0;
            border-radius: 6px;
            background: #f5c568;
            color: #172333;
            font: inherit;
            font-weight: bold;
            cursor: pointer;
        }

        button:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        input:focus-visible,
        textarea:focus-visible,
        button:focus-visible,
        summary:focus-visible,
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

    <h1>Consolidar reportes</h1>

    <p class="ayuda">
        Selecciona al menos dos reportes relacionados y redacta un resumen
        que reúna sus hallazgos, evite repetir información y señale
        posibles contradicciones entre las fuentes.
    </p>

    @if ($errors->any())
        <div class="errores" role="alert">
            <strong>Revisa los siguientes datos:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $seleccionados = array_map(
            'strval',
            (array) old('reportes', [])
        );
    @endphp

    <form method="POST"
          action="{{ route('inteligencia.informes.store') }}">
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
                Puedes leer cada contenido antes de seleccionarlo.
                Los reportes originales conservarán sus datos.
            </p>

            @forelse ($reportes as $reporte)
                <article class="reporte">
                    <label class="seleccion"
                           for="reporte-{{ $reporte->ID_reporte }}">
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

                    <p class="metadatos">
                        Fuente: {{ $reporte->Fuente ?: 'Sin fuente registrada' }}
                        <br>
                        Confiabilidad:
                        {{ $reporte->Confiabilidad ?: 'Sin clasificar' }}
                        <br>
                        Fecha:
                        {{ $reporte->Fecha_reporte?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                    </p>

                    <details>
                        <summary>Leer contenido</summary>
                        <p class="contenido">{{ $reporte->Contenido }}</p>
                    </details>
                </article>
            @empty
                <p>No hay reportes disponibles para consolidar.</p>
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
            <p class="ayuda">
                Debes registrar al menos dos reportes antes de consolidar.
            </p>
        @endif

        <div class="acciones">
            <button type="submit" @disabled($reportes->count() < 2)>
                Guardar informe consolidado
            </button>

            <a href="{{ route('inteligencia.informes.index') }}">
                Cancelar
            </a>
        </div>
    </form>
</main>
</body>
</html>