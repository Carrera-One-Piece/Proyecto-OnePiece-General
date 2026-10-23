<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel de pruebas · Eventos · Red de Gobierno</title>
    <style>
        :root {
            --fondo: #0a1a2f;
            --panel: #10264a;
            --panel-2: #0d2040;
            --borde: #24436b;
            --texto: #e8eef7;
            --suave: #9db3cf;
            --oro: #e3b04b;
            --ok: #3ecf8e;
            --aviso: #f5a524;
            --error: #ff6b6b;
            --azul: #4da3ff;
            --radio: 12px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--texto);
            background:
                radial-gradient(1200px 500px at 80% -10%, #16396b55, transparent),
                var(--fondo);
            min-height: 100vh;
        }
        .contenedor { max-width: 1180px; margin: 0 auto; padding: 1.5rem 1.2rem 3rem; }

        /* Encabezado */
        header.top { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.4rem; }
        .emblema { width: 46px; height: 46px; color: var(--oro); flex: none; }
        .titulos h1 { margin: 0; font-size: 1.45rem; letter-spacing: .02em; }
        .titulos p { margin: .15rem 0 0; color: var(--suave); font-size: .9rem; }
        .usuario { margin-left: auto; text-align: right; font-size: .85rem; color: var(--suave); line-height: 1.5; }
        .usuario strong { color: var(--texto); }
        .insignia {
            display: inline-block; padding: .15rem .6rem; border-radius: 999px;
            background: #e3b04b22; color: var(--oro); border: 1px solid #e3b04b55; font-size: .75rem; font-weight: 600;
        }
        .volver { color: var(--azul); text-decoration: none; font-size: .85rem; }
        .volver:hover { text-decoration: underline; }

        /* Rejilla principal */
        .rejilla { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.2rem; align-items: start; }
        .tarjeta {
            background: linear-gradient(180deg, var(--panel), var(--panel-2));
            border: 1px solid var(--borde); border-radius: var(--radio); padding: 1.2rem;
            box-shadow: 0 10px 30px #00000040;
        }
        .tarjeta h2 { margin: 0 0 .9rem; font-size: 1rem; color: var(--oro); letter-spacing: .04em; text-transform: uppercase; }
        .tarjeta h3 { margin: 1rem 0 .4rem; font-size: .8rem; color: var(--suave); text-transform: uppercase; letter-spacing: .06em; }

        /* Casos rápidos */
        .chips { display: flex; flex-wrap: wrap; gap: .4rem; }
        .chip {
            border: 1px solid var(--borde); background: #0b1b36; color: var(--texto);
            padding: .4rem .7rem; border-radius: 999px; font-size: .82rem; cursor: pointer;
            transition: transform .08s, border-color .15s, background .15s;
        }
        .chip:hover:not(:disabled) { border-color: var(--oro); background: #12294a; transform: translateY(-1px); }
        .chip:disabled { opacity: .4; cursor: not-allowed; }
        .chip.lectura { border-left: 3px solid var(--ok); }
        .chip.escritura { border-left: 3px solid var(--azul); }
        .chip.borrar { border-left: 3px solid var(--error); }
        .chip.valida { border-left: 3px solid var(--aviso); }

        /* Método */
        .metodos { display: grid; grid-template-columns: repeat(4, 1fr); gap: .4rem; margin-bottom: .8rem; }
        .metodos input { position: absolute; opacity: 0; pointer-events: none; }
        .metodos label {
            text-align: center; padding: .55rem 0; border-radius: 8px; cursor: pointer;
            border: 1px solid var(--borde); background: #0b1b36; font-weight: 700; font-size: .85rem; color: var(--suave);
        }
        .metodos input:checked + label.m-GET { background: #3ecf8e22; border-color: var(--ok); color: var(--ok); }
        .metodos input:checked + label.m-POST { background: #4da3ff22; border-color: var(--azul); color: var(--azul); }
        .metodos input:checked + label.m-PUT { background: #f5a52422; border-color: var(--aviso); color: var(--aviso); }
        .metodos input:checked + label.m-DELETE { background: #ff6b6b22; border-color: var(--error); color: var(--error); }

        .url {
            font-family: ui-monospace, Consolas, monospace; background: #07142a; border: 1px dashed var(--borde);
            border-radius: 8px; padding: .55rem .7rem; font-size: .88rem; color: var(--oro); margin-bottom: .6rem; overflow-x: auto;
        }

        label.campo { display: block; font-size: .78rem; color: var(--suave); margin: .65rem 0 .25rem; font-weight: 600; }
        input[type=text], textarea {
            width: 100%; background: #07142a; border: 1px solid var(--borde); color: var(--texto);
            border-radius: 8px; padding: .55rem .65rem; font-size: .95rem; font-family: inherit;
        }
        input[type=text]:focus, textarea:focus { outline: none; border-color: var(--oro); box-shadow: 0 0 0 3px #e3b04b22; }
        .dos { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; }

        .acciones { display: flex; gap: .6rem; margin-top: 1rem; }
        button.principal {
            flex: 1; border: 0; border-radius: 8px; padding: .75rem 1rem; font-weight: 700; font-size: .95rem; cursor: pointer;
            background: linear-gradient(180deg, #f0c363, var(--oro)); color: #1b1405;
        }
        button.principal:hover { filter: brightness(1.07); }
        button.secundario {
            border: 1px solid var(--borde); background: transparent; color: var(--suave);
            border-radius: 8px; padding: .75rem 1rem; cursor: pointer;
        }
        button.secundario:hover { color: var(--texto); border-color: var(--suave); }

        /* Respuesta */
        .estado { display: flex; align-items: center; gap: .7rem; flex-wrap: wrap; margin-bottom: .8rem; min-height: 2rem; }
        .codigo { padding: .25rem .7rem; border-radius: 999px; font-weight: 800; font-size: .9rem; }
        .codigo.c2 { background: #3ecf8e22; color: var(--ok); border: 1px solid var(--ok); }
        .codigo.c4 { background: #f5a52422; color: var(--aviso); border: 1px solid var(--aviso); }
        .codigo.c5 { background: #ff6b6b22; color: var(--error); border: 1px solid var(--error); }
        .ruta { font-family: ui-monospace, Consolas, monospace; font-size: .85rem; color: var(--suave); }
        .mensaje { padding: .8rem 1rem; border-radius: 8px; margin-bottom: .8rem; font-size: .92rem; line-height: 1.45; }
        .mensaje.ok { background: #3ecf8e18; border: 1px solid #3ecf8e55; }
        .mensaje.aviso { background: #f5a52418; border: 1px solid #f5a52455; }
        .mensaje.error { background: #ff6b6b18; border: 1px solid #ff6b6b55; }
        .mensaje ul { margin: .4rem 0 0; padding-left: 1.2rem; }
        .vacio { color: var(--suave); font-size: .92rem; padding: 1.2rem 0; text-align: center; }

        .tabla-scroll { overflow-x: auto; border: 1px solid var(--borde); border-radius: 8px; margin-bottom: .8rem; }
        table { width: 100%; border-collapse: collapse; font-size: .84rem; }
        th { text-align: left; background: #0b1b36; color: var(--suave); font-weight: 600; padding: .5rem .6rem; white-space: nowrap; }
        td { padding: .5rem .6rem; border-top: 1px solid #1a3558; vertical-align: top; }
        tr:hover td { background: #ffffff06; }
        .nivel { padding: .12rem .5rem; border-radius: 999px; font-size: .74rem; font-weight: 700; white-space: nowrap; }
        .nivel.Baja { background: #3ecf8e22; color: var(--ok); }
        .nivel.Media { background: #4da3ff22; color: var(--azul); }
        .nivel.Alta { background: #f5a52422; color: var(--aviso); }
        .nivel.Critica { background: #ff6b6b22; color: var(--error); }

        details summary { cursor: pointer; color: var(--suave); font-size: .82rem; margin-bottom: .4rem; }
        pre {
            margin: 0; background: #060f20; border: 1px solid var(--borde); border-radius: 8px; padding: .9rem;
            font-size: .8rem; line-height: 1.5; overflow: auto; max-height: 340px; color: #cfe0f5;
        }
        .ultimo { font-size: .8rem; color: var(--suave); margin-top: .6rem; }
        .ultimo b { color: var(--oro); }
    </style>
</head>
<body>
<div class="contenedor">

    <header class="top">
        {{-- Emblema propio (ancla dibujada en SVG), igual al de las pantallas de acceso. --}}
        <svg class="emblema" viewBox="0 0 64 64" fill="none" stroke="currentColor"
             stroke-width="4" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Emblema de ancla">
            <circle cx="32" cy="10" r="5"/>
            <path d="M32 15v40M20 26h24"/>
            <path d="M10 38c2 12 12 18 22 18s20-6 22-18"/>
            <path d="M10 38l-5 6M54 38l5 6"/>
        </svg>
        <div class="titulos">
            <h1>Módulo de Eventos</h1>
            <p>Panel de pruebas del CRUD de <code>/eventos</code> (RF13–RF15)</p>
        </div>
        <div class="usuario">
            <span class="insignia">{{ auth()->user()->organizacion?->Nombre ?? 'Sin organización' }}</span><br>
            <strong>{{ auth()->user()->Correo }}</strong><br>
            {{ auth()->user()->rol?->Nombre_rol ?? 'Sin rol' }}<br>
            <a class="volver" href="{{ url('/inicio') }}">← Volver al inicio</a>
        </div>
    </header>

    <div class="rejilla">

        {{-- ============ Petición ============ --}}
        <section class="tarjeta">
            <h2>Petición</h2>

            <h3 style="margin-top:0">Casos rápidos</h3>
            <div class="chips" id="chips">
                <button type="button" class="chip lectura" data-caso="listar">Listar todos</button>
                <button type="button" class="chip lectura" data-caso="ver1">Ver evento 1</button>
                <button type="button" class="chip lectura" data-caso="ver9999">Ver inexistente</button>
                <button type="button" class="chip escritura" data-caso="crear">Crear válido</button>
                <button type="button" class="chip escritura" data-caso="editar" id="chip-editar" disabled>Editar último creado</button>
                <button type="button" class="chip borrar" data-caso="borrar" id="chip-borrar" disabled>Borrar último creado</button>
                <button type="button" class="chip valida" data-caso="sinnombre">Sin nombre</button>
                <button type="button" class="chip valida" data-caso="tipo999">Tipo inexistente</button>
                <button type="button" class="chip valida" data-caso="isla999">Isla inexistente</button>
            </div>
            <div class="ultimo" id="ultimo"></div>

            <h3>Manual</h3>
            <div class="metodos">
                <input type="radio" name="metodo" id="m-GET" value="GET" checked><label class="m-GET" for="m-GET">GET</label>
                <input type="radio" name="metodo" id="m-POST" value="POST"><label class="m-POST" for="m-POST">POST</label>
                <input type="radio" name="metodo" id="m-PUT" value="PUT"><label class="m-PUT" for="m-PUT">PUT</label>
                <input type="radio" name="metodo" id="m-DELETE" value="DELETE"><label class="m-DELETE" for="m-DELETE">DELETE</label>
            </div>

            <div class="url" id="urlVista">GET /eventos</div>

            <label class="campo" for="id">ID del evento (vacío = todos / crear)</label>
            <input id="id" type="text" inputmode="numeric" placeholder="Ej. 17">

            <label class="campo" for="nombre">Nombre</label>
            <input id="nombre" type="text" placeholder="Nombre del evento">

            <div class="dos">
                <div>
                    <label class="campo" for="tipo">ID_tipo_evento</label>
                    <input id="tipo" type="text" inputmode="numeric" value="1">
                </div>
                <div>
                    <label class="campo" for="isla">ID_isla</label>
                    <input id="isla" type="text" inputmode="numeric" value="8">
                </div>
            </div>

            <div class="dos">
                <div>
                    <label class="campo" for="amenaza">Nivel_amenaza</label>
                    <input id="amenaza" type="text" placeholder="Baja, Media, Alta, Crítica">
                </div>
                <div>
                    <label class="campo" for="fecha">Fecha_evento</label>
                    <input id="fecha" type="text" placeholder="2026-10-22 10:00:00">
                </div>
            </div>

            <label class="campo" for="desc">Descripcion</label>
            <textarea id="desc" rows="2" placeholder="Opcional"></textarea>

            <div class="acciones">
                <button type="button" class="principal" id="enviar">Enviar petición</button>
                <button type="button" class="secundario" id="limpiar">Limpiar</button>
            </div>
        </section>

        {{-- ============ Respuesta ============ --}}
        <section class="tarjeta">
            <h2>Respuesta</h2>
            <div class="estado" id="estado"></div>
            <div id="mensaje"></div>
            <div id="tabla"></div>
            <div id="vacio" class="vacio">Aún no se ha enviado nada. Elige un caso rápido o llena el formulario.</div>
            <details id="detalle" hidden>
                <summary>Ver JSON completo</summary>
                <pre id="respuesta"></pre>
            </details>
        </section>

    </div>
</div>

<script>
    const $ = (id) => document.getElementById(id);
    let ultimoId = null;

    // ---------- utilidades ----------
    function metodoActual() {
        return document.querySelector('input[name="metodo"]:checked').value;
    }

    function actualizarUrl() {
        const id = $('id').value.trim();
        $('urlVista').textContent = metodoActual() + ' /eventos' + (id ? '/' + id : '');
    }

    function llenar(c) {
        document.getElementById('m-' + c.metodo).checked = true;
        $('id').value = c.id ?? '';
        $('nombre').value = c.nombre ?? '';
        $('tipo').value = c.tipo ?? '1';
        $('isla').value = c.isla ?? '8';
        $('amenaza').value = c.amenaza ?? '';
        $('fecha').value = c.fecha ?? '';
        $('desc').value = c.desc ?? '';
        actualizarUrl();
    }

    function marcarUltimo(id) {
        ultimoId = id;
        $('chip-editar').disabled = id === null;
        $('chip-borrar').disabled = id === null;
        $('ultimo').innerHTML = id === null ? '' : 'Último evento creado en esta sesión: <b>#' + id + '</b>';
    }

    const CASOS = {
        listar:    () => ({ metodo: 'GET' }),
        ver1:      () => ({ metodo: 'GET', id: 1 }),
        ver9999:   () => ({ metodo: 'GET', id: 9999 }),
        crear:     () => ({ metodo: 'POST', nombre: 'Evento de prueba', tipo: 1, isla: 8, amenaza: 'Media', desc: 'Creado desde el panel de pruebas' }),
        editar:    () => ({ metodo: 'PUT', id: ultimoId, nombre: 'Evento editado' }),
        borrar:    () => ({ metodo: 'DELETE', id: ultimoId }),
        sinnombre: () => ({ metodo: 'POST', nombre: '', tipo: 1, isla: 8 }),
        tipo999:   () => ({ metodo: 'POST', nombre: 'X', tipo: 999, isla: 8 }),
        isla999:   () => ({ metodo: 'POST', nombre: 'X', tipo: 1, isla: 999 }),
    };

    // ---------- pintar la respuesta ----------
    function limpiarRespuesta() {
        $('estado').innerHTML = '';
        $('mensaje').innerHTML = '';
        $('tabla').innerHTML = '';
    }

    function hora(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        return isNaN(d) ? iso : d.toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' });
    }

    function claseNivel(n) {
        return (n || '').normalize('NFD').replace(/[̀-ͯ]/g, '');
    }

    function celda(tr, texto, clase) {
        const td = document.createElement('td');
        if (clase) {
            const s = document.createElement('span');
            s.className = clase;
            s.textContent = texto;
            td.appendChild(s);
        } else {
            td.textContent = texto;
        }
        tr.appendChild(td);
    }

    function pintarTabla(eventos) {
        if (!eventos.length) {
            $('tabla').innerHTML = '<div class="vacio">No hay eventos.</div>';
            return;
        }
        const cont = document.createElement('div');
        cont.className = 'tabla-scroll';
        const t = document.createElement('table');
        t.innerHTML = '<thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Isla</th><th>Amenaza</th><th>Fecha</th></tr></thead>';
        const tb = document.createElement('tbody');
        for (const e of eventos) {
            const tr = document.createElement('tr');
            celda(tr, '#' + e.ID_evento);
            celda(tr, e.Nombre ?? '');
            celda(tr, e.ID_tipo_evento ?? '');
            celda(tr, e.ID_isla ?? '');
            if (e.Nivel_amenaza) celda(tr, e.Nivel_amenaza, 'nivel ' + claseNivel(e.Nivel_amenaza));
            else celda(tr, '—');
            celda(tr, hora(e.Fecha_evento));
            tb.appendChild(tr);
        }
        t.appendChild(tb);
        cont.appendChild(t);
        $('tabla').appendChild(cont);
    }

    function pintarMensaje(clase, titulo, lista) {
        const d = document.createElement('div');
        d.className = 'mensaje ' + clase;
        const b = document.createElement('strong');
        b.textContent = titulo;
        d.appendChild(b);
        if (lista && lista.length) {
            const ul = document.createElement('ul');
            for (const l of lista) {
                const li = document.createElement('li');
                li.textContent = l;
                ul.appendChild(li);
            }
            d.appendChild(ul);
        }
        $('mensaje').appendChild(d);
    }

    function pintar(res, datos, metodo, url) {
        limpiarRespuesta();
        $('vacio').hidden = true;

        const grupo = Math.floor(res.status / 100);
        const cod = document.createElement('span');
        cod.className = 'codigo c' + (grupo === 2 ? '2' : grupo === 5 ? '5' : '4');
        cod.textContent = 'HTTP ' + res.status;
        const ruta = document.createElement('span');
        ruta.className = 'ruta';
        ruta.textContent = metodo + ' ' + url;
        $('estado').appendChild(cod);
        $('estado').appendChild(ruta);

        if (Array.isArray(datos)) {
            pintarTabla(datos);
        } else if (res.ok) {
            pintarMensaje('ok', datos.message || 'Operación correcta');
            if (datos.data && datos.data.ID_evento) pintarTabla([datos.data]);
        } else if (res.status === 403) {
            pintarMensaje('error', 'Acceso denegado', ['Este rol no tiene permiso para esta acción.']);
        } else if (res.status === 404) {
            pintarMensaje('aviso', 'No encontrado', ['El evento no existe (o ya fue borrado).']);
        } else if (res.status === 422 && datos.errors) {
            const lista = [];
            for (const [campo, msgs] of Object.entries(datos.errors)) {
                for (const m of msgs) lista.push(campo + ': ' + m);
            }
            pintarMensaje('aviso', 'Datos no válidos (422)', lista);
        } else if (res.status === 401 || res.status === 419) {
            pintarMensaje('aviso', 'Sesión no válida', ['Vuelve a iniciar sesión y recarga esta página.']);
        } else {
            pintarMensaje('error', datos.message || 'Error del servidor');
        }

        $('detalle').hidden = false;
        $('respuesta').textContent = JSON.stringify(datos, null, 2);
    }

    // ---------- enviar ----------
    async function enviar() {
        const metodo = metodoActual();
        const id = $('id').value.trim();
        const url = '/eventos' + (id ? '/' + encodeURIComponent(id) : '');

        const campos = {
            Nombre: $('nombre').value,
            ID_tipo_evento: $('tipo').value,
            ID_isla: $('isla').value,
            Nivel_amenaza: $('amenaza').value,
            Fecha_evento: $('fecha').value,
            Descripcion: $('desc').value,
        };
        const cuerpo = {};
        for (const [k, v] of Object.entries(campos)) {
            if (v !== '') cuerpo[k] = v;
        }

        const opciones = {
            method: metodo,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        };
        if (metodo === 'POST' || metodo === 'PUT') opciones.body = JSON.stringify(cuerpo);

        $('enviar').disabled = true;
        try {
            const res = await fetch(url, opciones);
            const datos = await res.json().catch(() => ({ message: 'La respuesta no es JSON' }));
            pintar(res, datos, metodo, url);

            // Recordar el último creado / olvidar el borrado.
            if (metodo === 'POST' && res.status === 201 && datos.data) marcarUltimo(datos.data.ID_evento);
            if (metodo === 'DELETE' && res.ok && String(ultimoId) === id) marcarUltimo(null);
        } catch (e) {
            limpiarRespuesta();
            $('vacio').hidden = true;
            pintarMensaje('error', 'Error de red', [String(e)]);
        } finally {
            $('enviar').disabled = false;
        }
    }

    // ---------- eventos de la página ----------
    document.querySelectorAll('.chip').forEach((b) => {
        b.addEventListener('click', () => {
            llenar(CASOS[b.dataset.caso]());
            enviar();
        });
    });
    document.querySelectorAll('input[name="metodo"]').forEach((r) => r.addEventListener('change', actualizarUrl));
    $('id').addEventListener('input', actualizarUrl);
    $('enviar').addEventListener('click', enviar);
    $('limpiar').addEventListener('click', () => {
        llenar({ metodo: 'GET' });
        limpiarRespuesta();
        $('vacio').hidden = false;
        $('detalle').hidden = true;
    });

    actualizarUrl();
</script>
</body>
</html>