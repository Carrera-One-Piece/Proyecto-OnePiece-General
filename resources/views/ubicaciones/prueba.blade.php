<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gran Línea · Registro de Ubicaciones</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pirata+One&family=Lora:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --mar: #0c2c57;
            --mar-hondo: #071e3d;
            --pergamino: #f4e8c6;
            --pergamino-2: #e8d6a2;
            --tinta: #2b1a0b;
            --madera: #4a2c17;
            --madera-hondo: #321d0e;
            --oro: #f2b632;
            --rojo: #d4381f;
            --verde: #2e8a4e;
            --azul: #3b6cb3;
            --naranja: #d9822b;
            --titulo: 'Pirata One', 'Georgia', serif;
            --cuerpo: 'Lora', 'Georgia', 'Times New Roman', serif;
            --datos: ui-monospace, Consolas, 'Courier New', monospace;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: var(--cuerpo);
            color: var(--pergamino);
            background:
                repeating-linear-gradient(0deg, transparent 0 34px, #ffffff08 34px 35px),
                radial-gradient(1100px 520px at 70% -10%, #1b4a86 0%, transparent 70%),
                var(--mar);
        }
        /* La Red Line: dos franjas rojas a los lados */
        body::before, body::after {
            content: ""; position: fixed; top: 0; bottom: 0; width: 14px; z-index: 5;
            background: repeating-linear-gradient(0deg, var(--rojo) 0 38px, #a82c16 38px 40px);
        }
        body::before { left: 0; }
        body::after { right: 0; }

        .contenedor { max-width: 1180px; margin: 0 auto; padding: 1.1rem 2.2rem 3rem; }

        .franja {
            display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
            font-size: .72rem; letter-spacing: .12em; color: #b9c8de;
        }
        .franja b { color: var(--oro); font-weight: 700; }

        header.top { display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap; margin: 1.1rem 0 1.8rem; }
        .brujula { width: 88px; height: 88px; flex: none; }
        .titulos h1 { margin: 0; font-family: var(--titulo); font-weight: 400; font-size: clamp(2.2rem, 5vw, 3.4rem); line-height: .98; color: #f8ecc9; }
        .titulos h1 span { color: var(--oro); }
        .titulos p { margin: .45rem 0 0; font-style: italic; color: #b9c8de; font-size: .95rem; }
        .titulos code { font-family: var(--datos); font-style: normal; font-size: .88rem; }
        .usuario { margin-left: auto; text-align: right; font-size: .92rem; line-height: 1.55; color: #cfdaea; }
        .usuario strong { color: #fff4d4; }
        .insignia {
            display: inline-block; padding: .12rem .8rem; border-radius: 6px;
            background: var(--oro); color: #3a2605; font-weight: 700; font-size: .78rem; letter-spacing: .08em;
        }
        .volver { color: var(--oro); text-decoration: none; }
        .volver:hover, .volver:focus-visible { text-decoration: underline; }

        .rejilla { display: grid; grid-template-columns: minmax(320px, 1fr) minmax(320px, 1.05fr); gap: 1.6rem; align-items: start; }
        @media (max-width: 880px) { .rejilla { grid-template-columns: 1fr; } .usuario { margin-left: 0; text-align: left; } }

        /* ---------- Mesa del navegante (madera) ---------- */
        .mesa {
            background:
                repeating-linear-gradient(90deg, transparent 0 78px, #00000033 78px 80px),
                linear-gradient(180deg, var(--madera), var(--madera-hondo));
            border: 2px solid #1f1108; border-radius: 16px; padding: 1.4rem 1.4rem 1.6rem;
            box-shadow: 0 18px 40px #00000066, inset 0 1px 0 #ffffff1f;
        }
        .mesa h2 { margin: 0 0 1rem; font-family: var(--titulo); font-weight: 400; font-size: 1.7rem; color: var(--oro); }
        .mesa h3 { margin: 1.3rem 0 .6rem; font-size: .8rem; letter-spacing: .1em; color: #cdb48d; font-weight: 600; }

        .rumbos { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: .6rem; }
        .rumbo {
            display: flex; align-items: center; gap: .6rem; text-align: left; cursor: pointer;
            padding: .55rem .6rem; border-radius: 10px; border: 2px solid var(--verde);
            background: linear-gradient(180deg, #f6ecce, var(--pergamino-2)); color: var(--tinta);
            font-family: var(--cuerpo);
        }
        .rumbo:hover { filter: brightness(1.05); }
        .rumbo:focus-visible, .metodo:focus-visible, button.principal:focus-visible, button.secundario:focus-visible,
        select:focus-visible, input:focus-visible { outline: 3px solid var(--oro); outline-offset: 2px; }
        .rumbo .icono {
            width: 34px; height: 34px; flex: none; border-radius: 50%; display: grid; place-items: center;
            background: #fff8e2; border: 1px solid #00000022; font-size: 1.05rem;
        }
        .rumbo b { display: block; font-size: .9rem; line-height: 1.15; }
        .rumbo small { display: block; font-size: .72rem; color: #6a5230; margin-top: .1rem; }
        .rumbo.exito { border-color: var(--verde); }
        .rumbo.coord { border-color: var(--naranja); }
        .rumbo.peligro { border-color: var(--rojo); }
        .rumbo.dato { border-color: var(--azul); }

        .url {
            font-family: var(--datos); font-size: .9rem; color: var(--oro);
            background: #1a0e05; border: 1px dashed #8a6a3e; border-radius: 8px; padding: .55rem .7rem; overflow-x: auto;
        }
        label.campo { display: block; margin: .8rem 0 .25rem; font-size: .84rem; color: #e2cfa8; font-weight: 600; }
        input[type=text], select {
            width: 100%; padding: .6rem .7rem; font-size: 1rem; font-family: var(--cuerpo);
            border-radius: 8px; border: 2px solid #7a5a32; background: #fbf2d6; color: var(--tinta);
        }
        .dos { display: grid; grid-template-columns: 1fr 1fr; gap: .8rem; }
        .acciones { display: flex; gap: .7rem; margin-top: 1.2rem; }
        button.principal {
            flex: 1; padding: .8rem 1rem; border: 2px solid #a8741a; border-radius: 10px; cursor: pointer;
            background: linear-gradient(180deg, #ffd467, var(--oro)); color: #3a2605; font-family: var(--titulo); font-size: 1.3rem;
        }
        button.principal:hover { filter: brightness(1.06); }
        button.principal:disabled { opacity: .6; cursor: wait; }
        button.secundario {
            padding: .8rem 1.1rem; border-radius: 10px; cursor: pointer; font-family: var(--cuerpo);
            border: 2px solid #7a5a32; background: transparent; color: #e2cfa8;
        }
        button.secundario:hover { background: #ffffff12; }
        .nota { margin: .9rem 0 0; font-size: .8rem; color: #cdb48d; font-style: italic; }

        /* ---------- Bitácora (pergamino) ---------- */
        .bitacora {
            background:
                radial-gradient(600px 300px at 20% 0%, #fff6dc 0%, transparent 70%),
                linear-gradient(180deg, var(--pergamino), var(--pergamino-2));
            color: var(--tinta); border-radius: 6px 16px 16px 6px; padding: 1.4rem 1.6rem 1.8rem;
            border-left: 10px solid #7a4a22; box-shadow: 0 18px 40px #00000066;
            min-height: 420px;
        }
        .bitacora h2 { margin: 0 0 1.1rem; font-family: var(--titulo); font-weight: 400; font-size: 1.8rem; }
        .estado { display: flex; align-items: center; gap: .9rem; margin-bottom: 1rem; }
        .sello {
            width: 62px; height: 62px; flex: none; border-radius: 50%; display: grid; place-items: center;
            font-family: var(--titulo); font-size: 1.5rem; color: #fff; background: var(--verde);
            border: 3px solid #ffffff66; box-shadow: 0 4px 10px #0004;
        }
        .sello.c4 { background: var(--naranja); }
        .sello.c5 { background: var(--rojo); }
        .estado .ruta { font-family: var(--datos); font-size: .95rem; font-weight: 700; }
        .estado .quien { font-size: .8rem; font-style: italic; color: #6a5230; }

        .mensaje { padding: .85rem 1rem; border-radius: 8px; margin-bottom: 1rem; line-height: 1.45; border: 2px solid; }
        .mensaje strong { display: block; }
        .mensaje.ok { background: #cfe5c9; border-color: var(--verde); }
        .mensaje.aviso { background: #f6dfb4; border-color: var(--naranja); }
        .mensaje.error { background: #f3c9c0; border-color: var(--rojo); }
        .mensaje ul { margin: .4rem 0 0; padding-left: 1.2rem; }

        .vacio { padding: 2.2rem 0; text-align: center; color: #6a5230; font-style: italic; }

        /* Cartel de ubicación registrada */
        .cartel {
            width: 250px; margin: .4rem 0 1rem; padding: .8rem .8rem 1rem; text-align: center; position: relative;
            background: linear-gradient(180deg, #e3c98a, #cfae66); border: 2px solid #7a5a2c;
            box-shadow: 0 8px 18px #0004; transform: rotate(-1.5deg);
        }
        .cartel::before {
            content: ""; position: absolute; top: -9px; left: 50%; width: 16px; height: 16px; margin-left: -8px;
            border-radius: 50%; background: radial-gradient(circle at 35% 35%, #d8d8d8, #7d7d7d);
        }
        .cartel .titulo-cartel { font-family: var(--titulo); font-size: 2.1rem; line-height: 1; letter-spacing: .04em; }
        .cartel .foto {
            margin: .5rem 0; height: 110px; display: grid; place-items: center; font-size: 3rem;
            background: repeating-linear-gradient(45deg, #d9bb79 0 8px, #d3b36e 8px 16px); border: 2px solid #7a5a2c;
        }
        .cartel .entidad { font-family: var(--titulo); font-size: 1.35rem; }
        .cartel .coord { font-family: var(--datos); font-size: .95rem; font-weight: 700; margin-top: .2rem; }
        .cartel .tipo-cartel {
            position: absolute; right: -10px; top: 70px; transform: rotate(8deg); padding: .1rem .5rem;
            background: #fff; border: 2px solid var(--azul); color: var(--azul); font-family: var(--titulo); font-size: .85rem;
        }

        details summary { cursor: pointer; font-size: .85rem; color: #6a5230; margin-bottom: .4rem; }
        pre {
            margin: 0; padding: .9rem; max-height: 260px; overflow: auto; font-size: .8rem; line-height: 1.5;
            background: #2a1a0c; color: #f1deb0; border-radius: 8px;
        }
        @media (prefers-reduced-motion: no-preference) {
            .cartel { animation: caer .45s ease-out; }
            @keyframes caer { from { transform: translateY(-14px) rotate(-6deg); opacity: 0; } to { transform: rotate(-1.5deg); opacity: 1; } }
        }
    </style>
</head>
<body>
<div class="contenedor">

    <div class="franja">
        <span><b>RED LINE</b> · GRAN LÍNEA · <b>GOBIERNO MUNDIAL</b></span>
        <span>MARINA · CIPHER POL · GOBIERNO AFILIADO</span>
    </div>

    <header class="top">
        <svg class="brujula" viewBox="0 0 100 100" role="img" aria-label="Brújula">
            <circle cx="50" cy="50" r="44" fill="#16345e" stroke="#f2b632" stroke-width="6"/>
            <circle cx="50" cy="50" r="34" fill="none" stroke="#f2b63266" stroke-width="1.5"/>
            <path d="M50 14 L58 50 L50 86 L42 50 Z" fill="#d4381f"/>
            <path d="M50 50 L58 50 L50 86 L42 50 Z" fill="#e9eef7"/>
            <circle cx="50" cy="50" r="4" fill="#f2b632"/>
        </svg>
        <div class="titulos">
            <h1>Gran <span>Línea</span><br>Registro de Ubicaciones</h1>
            <p>Módulo de Navegación · Panel de pruebas de <code>/ubicaciones</code></p>
        </div>
        <div class="usuario">
            <span class="insignia">{{ auth()->user()->organizacion?->Nombre ?? 'Sin organización' }}</span><br>
            <strong>{{ auth()->user()->Correo }}</strong><br>
            {{ auth()->user()->rol?->Nombre_rol ?? 'Sin rol' }}<br>
            <a class="volver" href="{{ url('/inicio') }}">← Volver al cuartel</a>
        </div>
    </header>

    <div class="rejilla">

        {{-- ============ Mesa del navegante ============ --}}
        <section class="mesa" aria-labelledby="t-mesa">
            <h2 id="t-mesa">Mesa del navegante</h2>

            <h3 style="margin-top:0">RUMBOS RÁPIDOS</h3>
            <div class="rumbos">
                <button type="button" class="rumbo exito" data-caso="valido">
                    <span class="icono">⛵</span><span><b>Ubicación válida</b></span>
                </button>
                <button type="button" class="rumbo coord" data-caso="xtexto">
                    <span class="icono">🔤</span><span><b>X no numérica</b></span>
                </button>
                <button type="button" class="rumbo coord" data-caso="xfuera">
                    <span class="icono">🧭</span><span><b>X fuera del mapa</b></span>
                </button>
                <button type="button" class="rumbo coord" data-caso="yfuera">
                    <span class="icono">🌊</span><span><b>Y fuera del mapa</b></span>
                </button>
                <button type="button" class="rumbo dato" data-caso="idfalso">
                    <span class="icono">❓</span><span><b>ID inexistente</b></span>
                </button>
                <button type="button" class="rumbo dato" data-caso="tipofalso">
                    <span class="icono">🌀</span><span><b>Tipo inválido</b></span>
                </button>
                <button type="button" class="rumbo peligro" data-caso="vacio">
                    <span class="icono">💥</span><span><b>Sin coordenadas</b></span>
                </button>
            </div>

            <label class="campo" for="tipo">Tipo de entidad</label>
            <select id="tipo">
                <option value="tripulacion">tripulacion</option>
                <option value="personaje">personaje</option>
                <option value="barco" selected>barco</option>
                <option value="organizacion">organizacion (incluye la Marina)</option>
            </select>

            <label class="campo" for="id">ID de la entidad</label>
            <input id="id" type="text" inputmode="numeric" value="8">

            <div class="dos">
                <div>
                    <label class="campo" for="x">Coordenada X</label>
                    <input id="x" type="text" inputmode="decimal" value="500">
                </div>
                <div>
                    <label class="campo" for="y">Coordenada Y</label>
                    <input id="y" type="text" inputmode="decimal" value="400">
                </div>
            </div>

            <div class="acciones">
                <button type="button" class="principal" id="enviar">Enviar rumbo</button>
                <button type="button" class="secundario" id="limpiar">Limpiar</button>
            </div>
        </section>

        {{-- ============ Bitácora de la Gran Línea ============ --}}
        <section class="bitacora" aria-labelledby="t-bitacora" aria-live="polite">
            <h2 id="t-bitacora">Bitácora de la Gran Línea</h2>

            <div id="estado"></div>
            <div id="mensaje"></div>
            <div id="cartel"></div>
            <div id="vacio" class="vacio">Aún no hay rumbos anotados. Elige un rumbo rápido o traza uno a mano.</div>

            <details id="detalle" hidden>
                <summary>Ver respuesta completa (JSON)</summary>
                <pre id="respuesta"></pre>
            </details>
        </section>

    </div>
</div>

<script>
    const $ = (id) => document.getElementById(id);

    const ICONOS = { tripulacion: '🏴‍☠️', personaje: '🧑‍✈️', barco: '⛵', organizacion: '⚓' };

    const CASOS = {
        valido:    { tipo: 'barco', id: '8',    x: '500',   y: '400' },
        xtexto:    { tipo: 'barco', id: '8',    x: 'abc',   y: '400' },
        xfuera:    { tipo: 'barco', id: '8',    x: '99999', y: '400' },
        yfuera:    { tipo: 'barco', id: '8',    x: '500',   y: '-5' },
        idfalso:   { tipo: 'barco', id: '9999', x: '500',   y: '400' },
        tipofalso: { tipo: 'pizza', id: '8',    x: '500',   y: '400' },
        vacio:     { tipo: 'barco', id: '8',    x: '',      y: '' },
    };

    function llenar(c) {
        $('tipo').value = c.tipo;
        $('id').value = c.id;
        $('x').value = c.x;
        $('y').value = c.y;
        actualizarUrl();
    }

    function actualizarUrl() {
        $('urlVista').textContent = 'PUT /ubicaciones/' + $('tipo').value + '/' + $('id').value.trim();
    }

    function limpiarRespuesta() {
        $('estado').innerHTML = '';
        $('mensaje').innerHTML = '';
        $('cartel').innerHTML = '';
    }

    function pintarEstado(status, metodo, url) {
        const grupo = Math.floor(status / 100);
        const caja = document.createElement('div');
        caja.className = 'estado';

        const sello = document.createElement('div');
        sello.className = 'sello ' + (grupo === 2 ? '' : grupo === 5 ? 'c5' : 'c4');
        sello.textContent = status;

        const texto = document.createElement('div');
        const ruta = document.createElement('div');
        ruta.className = 'ruta';
        ruta.textContent = metodo + ' ' + url;
        const quien = document.createElement('div');
        quien.className = 'quien';
        quien.textContent = 'Respuesta del Den Den Mushi';
        texto.appendChild(ruta);
        texto.appendChild(quien);

        caja.appendChild(sello);
        caja.appendChild(texto);
        $('estado').appendChild(caja);
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

    function pintarCartel(d) {
        const c = document.createElement('div');
        c.className = 'cartel';

        const t = document.createElement('div');
        t.className = 'titulo-cartel';
        t.textContent = 'WANTED';

        const foto = document.createElement('div');
        foto.className = 'foto';
        foto.textContent = ICONOS[d.tipo] || '📍';

        const nombre = document.createElement('div');
        nombre.className = 'entidad';
        nombre.textContent = d.tipo + ' #' + d.id;

        const coord = document.createElement('div');
        coord.className = 'coord';
        coord.textContent = 'X ' + d.x + ' · Y ' + d.y;

        const sello = document.createElement('div');
        sello.className = 'tipo-cartel';
        sello.textContent = 'validada';

        [t, foto, nombre, coord, sello].forEach((e) => c.appendChild(e));
        $('cartel').appendChild(c);
    }

    function pintar(res, datos, url, enviado) {
        limpiarRespuesta();
        $('vacio').hidden = true;
        pintarEstado(res.status, 'PUT', url);

        if (res.ok) {
            pintarMensaje('ok', datos.message || 'Ubicación válida: pasó todas las validaciones');
            pintarCartel(enviado);
        } else if (res.status === 403) {
            pintarMensaje('error', 'Acceso denegado', ['Este rol puede ver las ubicaciones, pero no modificarlas.']);
        } else if (res.status === 404) {
            pintarMensaje('aviso', 'No encontrado', ['La ruta o la entidad no existe.']);
        } else if (res.status === 422 && datos.errors) {
            const lista = [];
            for (const msgs of Object.values(datos.errors)) {
                for (const m of msgs) lista.push(m);
            }
            pintarMensaje('aviso', 'Datos no válidos', lista);
        } else if (res.status === 401 || res.status === 419) {
            pintarMensaje('aviso', 'Sesión no válida', ['Vuelve a iniciar sesión y recarga esta página.']);
        } else {
            pintarMensaje('error', datos.message || 'Error del servidor');
        }

        $('detalle').hidden = false;
        $('respuesta').textContent = JSON.stringify(datos, null, 2);
    }

    async function enviar() {
        const enviado = {
            tipo: $('tipo').value,
            id: $('id').value.trim(),
            x: $('x').value,
            y: $('y').value,
        };
        const url = '/ubicaciones/' + encodeURIComponent(enviado.tipo) + '/' + encodeURIComponent(enviado.id);

        const cuerpo = {};
        if (enviado.x !== '') cuerpo.x = enviado.x;
        if (enviado.y !== '') cuerpo.y = enviado.y;

        $('enviar').disabled = true;
        try {
            const res = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(cuerpo),
            });
            const datos = await res.json().catch(() => ({ message: 'La respuesta no es JSON' }));
            pintar(res, datos, url, enviado);
        } catch (e) {
            limpiarRespuesta();
            $('vacio').hidden = true;
            pintarMensaje('error', 'Error de red', [String(e)]);
        } finally {
            $('enviar').disabled = false;
        }
    }

    document.querySelectorAll('.rumbo').forEach((b) => {
        b.addEventListener('click', () => {
            llenar(CASOS[b.dataset.caso]);
            enviar();
        });
    });
    ['tipo', 'id'].forEach((i) => $(i).addEventListener('input', actualizarUrl));
    $('tipo').addEventListener('change', actualizarUrl);
    $('enviar').addEventListener('click', enviar);
    $('limpiar').addEventListener('click', () => {
        llenar({ tipo: 'barco', id: '8', x: '500', y: '400' });
        limpiarRespuesta();
        $('vacio').hidden = false;
        $('detalle').hidden = true;
    });

    actualizarUrl();
</script>
</body>
</html>