<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Prueba de validación de ubicaciones</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 640px; margin: 2rem auto; padding: 0 1rem; }
        label { display: block; margin-top: .8rem; font-weight: 600; }
        input, select, button { padding: .5rem; font-size: 1rem; width: 100%; box-sizing: border-box; }
        button { margin-top: 1rem; cursor: pointer; }
        .casos button { width: auto; margin: .2rem .2rem 0 0; font-size: .85rem; }
        pre { background: #111; color: #eee; padding: 1rem; border-radius: 6px; overflow-x: auto; }
        .ok { color: #2a9d4b; } .error { color: #e63946; }
    </style>
</head>
<body>
    <h1>Prueba: validación de ubicaciones</h1>
    <p>Página solo para verificar el <code>UbicacionRequest</code>. Sesión: <strong>{{ auth()->user()->Correo }}</strong></p>

    <div class="casos">
        <strong>Casos rápidos:</strong><br>
        <button type="button" onclick="llenar('barco', 8, 500, 400)">Válido</button>
        <button type="button" onclick="llenar('barco', 8, 'abc', 400)">X no numérica</button>
        <button type="button" onclick="llenar('barco', 8, 99999, 400)">X fuera del mapa</button>
        <button type="button" onclick="llenar('barco', 8, 500, -5)">Y fuera del mapa</button>
        <button type="button" onclick="llenar('barco', 9999, 500, 400)">ID inexistente</button>
        <button type="button" onclick="llenar('pizza', 8, 500, 400)">Tipo inválido</button>
        <button type="button" onclick="llenar('barco', 8, '', '')">Sin coordenadas</button>
    </div>

    <label for="tipo">Tipo de entidad</label>
    <select id="tipo">
        <option value="tripulacion">tripulacion</option>
        <option value="personaje">personaje</option>
        <option value="barco" selected>barco</option>
        <option value="organizacion">organizacion</option>
        <option value="pizza">pizza (inválido, para probar)</option>
    </select>

    <label for="id">ID de la entidad</label>
    <input id="id" type="text" value="8">

    <label for="x">Coordenada X</label>
    <input id="x" type="text" value="500">

    <label for="y">Coordenada Y</label>
    <input id="y" type="text" value="400">

    <button type="button" id="enviar">Enviar (PUT)</button>

    <h2>Respuesta</h2>
    <p id="estado">Aún no se ha enviado nada.</p>
    <pre id="respuesta"></pre>

    <script>
        const $ = (id) => document.getElementById(id);

        function llenar(tipo, id, x, y) {
            $('tipo').value = tipo;
            $('id').value = id;
            $('x').value = x;
            $('y').value = y;
        }

        $('enviar').addEventListener('click', async () => {
            const url = `/ubicaciones/${encodeURIComponent($('tipo').value)}/${encodeURIComponent($('id').value)}`;

            try {
                const res = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ x: $('x').value, y: $('y').value }),
                });

                const datos = await res.json().catch(() => ({ mensaje: 'La respuesta no es JSON' }));
                $('estado').innerHTML = `<span class="${res.ok ? 'ok' : 'error'}">HTTP ${res.status}</span> → PUT ${url}`;
                $('respuesta').textContent = JSON.stringify(datos, null, 2);
            } catch (e) {
                $('estado').innerHTML = '<span class="error">Error de red</span>';
                $('respuesta').textContent = String(e);
            }
        });
    </script>
</body>
</html>