<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrar reporte de inteligencia</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #0c2037;
            color: #eef2f6;
            font-family: Arial, sans-serif;
        }

        main {
            max-width: 800px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        a { color: #f5c568; }

        h1 { margin-top: 28px; }

        .descripcion { color: #b7c6d7; line-height: 1.5; }

        form {
            margin-top: 24px;
            padding: 24px;
            background: #142d47;
            border: 1px solid #35506c;
            border-radius: 8px;
        }

        label {
            display: block;
            margin: 20px 0 8px;
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #69839e;
            border-radius: 5px;
            background: #f6f8fb;
            color: #172333;
            font: inherit;
        }

        textarea { resize: vertical; }

        input:focus-visible,
        textarea:focus-visible,
        select:focus-visible,
        button:focus-visible,
        a:focus-visible {
            outline: 3px solid #f5c568;
            outline-offset: 3px;
        }

        .ayuda {
            color: #b7c6d7;
            font-size: 14px;
            line-height: 1.5;
        }

        .errores {
            padding: 16px;
            background: #532631;
            border: 1px solid #ec9ba9;
            border-radius: 6px;
        }

        .errores ul { margin-bottom: 0; }

        .acciones {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 28px;
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
    </style>
</head>
<body>
<main>
    <a href="{{ route('inteligencia.reportes.index') }}">
        ← Volver a los reportes
    </a>

    <h1>Registrar reporte de inteligencia</h1>

    <p class="descripcion">
        Registra la información recibida e identifica su fuente de origen.
        El informante es opcional; los demás campos son obligatorios.
    </p>

    @if ($errors->any())
        <div class="errores" role="alert">
            <strong>Revisa los siguientes campos:</strong>

            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('inteligencia.reportes.store') }}">
        @csrf

        <label for="titulo">Título</label>
        <input
            id="titulo"
            name="Titulo"
            type="text"
            maxlength="150"
            value="{{ old('Titulo') }}"
            required
        >

        <label for="contenido">Contenido</label>
        <textarea
            id="contenido"
            name="Contenido"
            rows="8"
            required
        >{{ old('Contenido') }}</textarea>

        <label for="fuente">Fuente de origen</label>
        <input
            id="fuente"
            name="Fuente"
            type="text"
            maxlength="150"
            value="{{ old('Fuente') }}"
            placeholder="Ejemplo: Marina — Base de Marineford"
            aria-describedby="ayuda-fuente"
            required
        >
        <p id="ayuda-fuente" class="ayuda">
            Indica la organización o fuente que proporcionó la información:
            Marina, Cipher Pol, gobierno afiliado u otra fuente.
        </p>

        <label for="confiabilidad">Confiabilidad declarada</label>
        <select id="confiabilidad" name="Confiabilidad" required>
            <option value="">Selecciona una opción</option>

            @foreach (['Alta', 'Media', 'Baja'] as $nivel)
                <option
                    value="{{ $nivel }}"
                    @selected(old('Confiabilidad') === $nivel)
                >
                    {{ $nivel }}
                </option>
            @endforeach
        </select>

        <label for="informante">Informante (opcional)</label>
        <select
            id="informante"
            name="ID_informante"
            aria-describedby="ayuda-informante"
        >
            <option value="">Sin informante identificado</option>

            @foreach ($informantes as $informante)
                <option
                    value="{{ $informante->ID_personaje }}"
                    @selected(
                        (string) old('ID_informante')
                        === (string) $informante->ID_personaje
                    )
                >
                    Personaje #{{ $informante->ID_personaje }}
                </option>
            @endforeach
        </select>

        <p id="ayuda-informante" class="ayuda">
            Selecciona un personaje solo si está identificado como informante.
            La fecha del reporte se asignará automáticamente al guardarlo.
        </p>

        <div class="acciones">
            <button type="submit">Guardar reporte</button>

            <a href="{{ route('inteligencia.reportes.index') }}">
                Cancelar
            </a>
        </div>
    </form>
</main>
</body>
</html>