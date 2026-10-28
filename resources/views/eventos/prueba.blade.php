<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Gran Línea · Registro de Eventos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;0,700;1,400&family=Pirata+One&family=Rye&display=swap" rel="stylesheet">
    <style>
        :root {
            --mar: #0a2747; --mar-2: #0f3a66; --mar-3: #1a5a95;
            --oro: #f2b632; --oro-oscuro: #a87812;
            --rojo: #c8321e; --rojo-oscuro: #86200f;
            --perg: #f3e3bd; --perg-2: #e6cf9b;
            --tinta: #3a2814; --tinta-suave: #6d5436;
            --madera: #4a2e1a; --madera-2: #2f1b0d;
            --ok: #2f7d4f; --aviso: #b8761b; --error: #a3321f;
            --titulo: "Pirata One", Georgia, serif;
            --cartel: "Rye", "Pirata One", Georgia, serif;
            --texto: "Lora", Georgia, serif;
        }
        body.tema-cp { --mar: #17161f; --mar-2: #25233a; --mar-3: #3b3658; --oro: #d8d2e8; --oro-oscuro: #8c85a6; }
        body.tema-gobierno { --mar: #34290d; --mar-2: #4a3c14; --mar-3: #6b5a22; --oro: #e8c352; --oro-oscuro: #a98a22; }

        * { box-sizing: border-box; }
        html { background: var(--mar); }
        body {
            margin: 0; font-family: var(--texto); color: var(--perg); min-height: 100vh; position: relative;
            background:
                radial-gradient(1000px 500px at 50% -10%, #ffffff14, transparent),
                repeating-linear-gradient(180deg, #ffffff07 0 2px, transparent 2px 30px),
                linear-gradient(180deg, var(--mar), var(--mar-2) 55%, var(--mar)) fixed;
        }
        /* Red Line: las dos paredes rojas a los lados de la Gran Línea */
        body::before, body::after {
            content: ""; position: fixed; top: 0; bottom: 0; width: 14px; z-index: 5;
            background: repeating-linear-gradient(180deg, var(--rojo) 0 40px, var(--rojo-oscuro) 40px 44px);
            box-shadow: 0 0 18px #0009;
        }
        body::before { left: 0; } body::after { right: 0; }

        /* ---------- Cabecera ---------- */
        header.proa { position: relative; padding: 1.2rem 2rem 3.6rem; }
        .franja { max-width: 1200px; margin: 0 auto .9rem; display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
            font-size: .72rem; letter-spacing: .28em; text-transform: uppercase; color: #f3e3bdab; }
        .franja b { color: var(--oro); font-weight: 700; }
        .proa-fila { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap; }

        /* Log Pose */
        .logpose { width: 92px; height: 92px; flex: none; filter: drop-shadow(0 5px 8px #0008); }
        .logpose .aguja { transform-origin: 50px 50px; animation: oscila 5s ease-in-out infinite; }
        @@keyframes oscila { 0%,100% { transform: rotate(-14deg); } 50% { transform: rotate(22deg); } }

        .titulos h1 { margin: 0; font-family: var(--titulo); font-weight: 400; font-size: clamp(2.2rem, 5vw, 3.4rem); line-height: .95; letter-spacing: .05em; color: var(--perg); text-shadow: 0 3px 0 #000a; }
        .titulos h1 span { color: var(--oro); }
        .titulos p { margin: .5rem 0 0; font-style: italic; color: #f3e3bdc4; }
        .capitan { margin-left: auto; text-align: right; font-size: .88rem; line-height: 1.6; color: #f3e3bdd0; }
        .capitan strong { color: var(--perg); }
        .sello-org { display: inline-block; padding: .14rem .9rem; border-radius: 4px; font-weight: 700; font-size: .76rem; letter-spacing: .12em; text-transform: uppercase; background: var(--oro); color: var(--madera-2); box-shadow: 0 2px 0 var(--oro-oscuro); }
        .capitan a { color: var(--oro); text-decoration: none; } .capitan a:hover { text-decoration: underline; }
        .olas { position: absolute; left: 0; right: 0; bottom: -1px; height: 36px; width: 100%; }
        .olas path { fill: var(--mar-2); }

        /* ---------- Rejilla ---------- */
        main { position: relative; z-index: 1; max-width: 1200px; margin: -.8rem auto 0; padding: 0 2rem 3.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.6rem; align-items: start; }
        h2.rotulo { margin: 0 0 1rem; font-family: var(--titulo); font-weight: 400; font-size: 1.6rem; letter-spacing: .05em; display: flex; align-items: center; gap: .6rem; }
        h3.sub { margin: 1.2rem 0 .55rem; font-size: .74rem; letter-spacing: .16em; text-transform: uppercase; font-weight: 700; }

        /* ---------- Mesa del navegante ---------- */
        .mesa { border-radius: 14px; padding: 1.3rem; background: repeating-linear-gradient(90deg, #0000 0 38px, #0000002c 38px 40px), linear-gradient(180deg, var(--madera), var(--madera-2)); border: 2px solid #1a0e06; box-shadow: 0 14px 30px #000a, inset 0 1px 0 #ffffff22; }
        .mesa h2.rotulo { color: var(--oro); } .mesa h3.sub { color: #f3e3bdaa; }

        .poses { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .5rem; }
        .pose { cursor: pointer; text-align: left; border: 2px solid #1a0e06; border-radius: 8px; padding: .5rem .6rem; color: var(--tinta); font: 600 .8rem var(--texto);
            background: linear-gradient(180deg, var(--perg), var(--perg-2)); display: flex; align-items: center; gap: .5rem; transition: transform .12s, filter .15s; }
        .pose:hover:not(:disabled) { transform: translateY(-2px); filter: brightness(1.07); }
        .pose:disabled { opacity: .4; cursor: not-allowed; }
        .pose i { font-style: normal; width: 1.7rem; height: 1.7rem; flex: none; display: grid; place-items: center; border-radius: 50%; font-size: .95rem; background: #fff8; border: 1.5px solid var(--tinta-suave); }
        .pose small { display: block; font-weight: 400; font-size: .68rem; color: var(--tinta-suave); }
        .pose.lectura { border-color: #2f7d4f; } .pose.escritura { border-color: #b8761b; } .pose.borrar { border-color: #a3321f; } .pose.valida { border-color: #3a5f9a; }

        .metodos { display: grid; grid-template-columns: repeat(4, 1fr); gap: .4rem; }
        .metodos input { position: absolute; opacity: 0; pointer-events: none; }
        .metodos label { text-align: center; padding: .55rem 0; cursor: pointer; font-weight: 700; font-size: .84rem; letter-spacing: .06em; border-radius: 6px; border: 2px solid #1a0e06; background: var(--madera-2); color: #f3e3bdaa; }
        .metodos input:checked + label { background: var(--oro); color: var(--madera-2); border-color: var(--oro-oscuro); }
        .metodos input:focus-visible + label { outline: 3px solid var(--perg); outline-offset: 2px; }

        .ruta-vista { font-family: ui-monospace, Consolas, monospace; font-size: .9rem; margin: .9rem 0 .2rem; padding: .55rem .75rem; background: #170c05; border: 1px dashed #f2b63288; border-radius: 6px; color: var(--oro); overflow-x: auto; }
        label.campo { display: block; margin: .75rem 0 .25rem; font-size: .8rem; font-weight: 600; letter-spacing: .04em; color: #f3e3bdc8; }
        input[type=text], textarea { width: 100%; font: 400 .98rem var(--texto); color: var(--tinta); background: var(--perg); border: 2px solid #1a0e06; border-radius: 6px; padding: .55rem .65rem; }
        input[type=text]::placeholder, textarea::placeholder { color: #6d543699; font-style: italic; }
        input[type=text]:focus, textarea:focus { outline: 3px solid var(--oro); outline-offset: 1px; }
        .dos { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem; }
        .acciones { display: flex; gap: .6rem; margin-top: 1.1rem; }
        button.zarpar { flex: 1; cursor: pointer; border: 2px solid var(--oro-oscuro); border-radius: 8px; padding: .75rem 1rem; font: 400 1.2rem var(--titulo); letter-spacing: .07em; color: var(--madera-2);
            background: linear-gradient(180deg, #ffd666, var(--oro)); box-shadow: 0 4px 0 var(--oro-oscuro), 0 8px 14px #0007; transition: transform .1s, box-shadow .1s; }
        button.zarpar:hover { filter: brightness(1.06); }
        button.zarpar:active { transform: translateY(3px); box-shadow: 0 1px 0 var(--oro-oscuro), 0 3px 8px #0007; }
        button.zarpar:disabled { opacity: .6; cursor: wait; }
        button.limpiar { cursor: pointer; background: transparent; color: #f3e3bdb5; border: 2px solid #f3e3bd55; border-radius: 8px; padding: .75rem 1rem; font: 600 .9rem var(--texto); }
        button.limpiar:hover { color: var(--perg); border-color: var(--perg); }
        .ultimo { margin-top: .7rem; font-size: .82rem; color: #f3e3bdb5; min-height: 1.2em; } .ultimo b { color: var(--oro); }

        /* ---------- Bitácora ---------- */
        .cuaderno { border-radius: 6px 14px 14px 6px; padding: 1.3rem 1.3rem 1.5rem 1.6rem; color: var(--tinta); min-height: 460px;
            background: radial-gradient(600px 300px at 90% 0%, #ffffff55, transparent), linear-gradient(180deg, var(--perg), var(--perg-2));
            border: 2px solid #7a5a2c; border-left: 14px solid var(--madera); box-shadow: 0 14px 30px #000a, inset 0 0 60px #7a5a2c33; }
        .cuaderno h2.rotulo { color: var(--tinta); }
        .estado { display: flex; align-items: center; gap: .9rem; flex-wrap: wrap; margin-bottom: .9rem; }
        .lacre { width: 58px; height: 58px; border-radius: 50%; display: grid; place-items: center; flex: none; font: 400 1.15rem var(--titulo); color: #fff7e6;
            background: radial-gradient(circle at 35% 30%, #d4553f, var(--rojo) 60%, #6b1e12); box-shadow: 0 3px 6px #0006, inset 0 -3px 6px #0004, inset 0 2px 3px #fff4;
            border: 3px double #f7c9bf55; transform: rotate(-6deg); }
        .lacre.c2 { background: radial-gradient(circle at 35% 30%, #58b27e, var(--ok) 60%, #1d5236); }
        .lacre.c4 { background: radial-gradient(circle at 35% 30%, #e6a752, var(--aviso) 60%, #6e4310); }
        .ruta-resp { font-family: ui-monospace, Consolas, monospace; font-size: .85rem; color: var(--tinta-suave); }
        .ruta-resp small { display: block; font-family: var(--texto); font-style: italic; }

        .nota { padding: .85rem 1rem; border-radius: 6px; margin-bottom: .9rem; border: 2px solid; line-height: 1.45; font-size: .95rem; }
        .nota.ok { background: #d0e4c5; border-color: var(--ok); } .nota.aviso { background: #f3dda9; border-color: var(--aviso); } .nota.error { background: #efc6bc; border-color: var(--error); }
        .nota ul { margin: .4rem 0 0; padding-left: 1.2rem; }
        .vacio { text-align: center; padding: 2rem 1rem; font-style: italic; color: var(--tinta-suave); }
        .vacio svg { width: 84px; height: 84px; color: var(--oro-oscuro); opacity: .75; display: block; margin: 0 auto .6rem; }

        /* ---------- Carteles SE BUSCA ---------- */
        .carteles { display: grid; grid-template-columns: repeat(auto-fill, minmax(215px, 1fr)); gap: 1.3rem; margin-bottom: .6rem; }
        .cartel { position: relative; padding: .9rem .9rem .8rem; background: #f6e8c4; color: #2e2010; text-align: center;
            border: 3px solid #5a3e1c; box-shadow: 0 8px 14px #3a28144d, inset 0 0 28px #b8975a66; animation: entrar .35s ease both; transition: transform .15s; }
        .cartel:nth-child(odd) { transform: rotate(-1deg); } .cartel:nth-child(even) { transform: rotate(.9deg); }
        .cartel:hover { transform: rotate(0) translateY(-4px) scale(1.02); }
        .cartel::before { content: ""; position: absolute; top: -9px; left: 50%; width: 15px; height: 15px; margin-left: -7px; border-radius: 50%; background: radial-gradient(circle at 35% 30%, #e8e8e8, #606060); box-shadow: 0 2px 3px #0007; }
        .cartel .wanted { font: 400 2.15rem/1 var(--cartel); letter-spacing: .03em; margin: .3rem 0 .45rem; }
        .cartel .foto { height: 108px; margin: 0 auto; border: 3px solid #2e2010; background: radial-gradient(circle at 50% 120%, #e8cf94, #cdb078); display: grid; place-items: center; position: relative; overflow: hidden; }
        .cartel .foto::after { content: ""; position: absolute; inset: 0; background: repeating-linear-gradient(135deg, transparent 0 7px, #5a3e1c14 7px 8px); }
        .cartel .foto .ico { font-size: 3rem; filter: drop-shadow(0 2px 0 #0003); z-index: 1; }
        .cartel .foto .nombre-isla { position: absolute; bottom: 3px; right: 5px; z-index: 1; font-size: .62rem; letter-spacing: .12em; text-transform: uppercase; color: #5a3e1c; font-weight: 700; }
        .cartel .alias { margin: .5rem 0 0; font: 400 1.15rem/1.1 var(--titulo); letter-spacing: .03em; text-transform: uppercase; }
        .cartel .tipo { font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; color: #6d5436; margin-top: .1rem; }
        .cartel .desc { margin: .45rem 0 0; font-size: .76rem; line-height: 1.35; font-style: italic; }
        .cartel .recompensa { margin-top: .55rem; padding-top: .45rem; border-top: 2px solid #2e2010; }
        .cartel .recompensa small { display: block; font-size: .58rem; letter-spacing: .26em; text-transform: uppercase; color: #6d5436; }
        .cartel .recompensa b { display: block; font: 400 1.5rem/1.1 var(--cartel); letter-spacing: .02em; }
        .cartel .recompensa b::before { content: "฿ "; }
        .cartel .pie { display: flex; justify-content: space-between; margin-top: .45rem; font-size: .62rem; letter-spacing: .2em; text-transform: uppercase; color: #6d5436; }
        .cartel .nivel { position: absolute; top: 58px; right: -8px; z-index: 2; transform: rotate(8deg); padding: .08rem .55rem; font: 400 .82rem var(--titulo); letter-spacing: .1em; text-transform: uppercase; background: #fbf1d6; border: 2px solid currentColor; border-radius: 3px; }
        .nivel.Baja { color: #2f7d4f; } .nivel.Media { color: #2a5f9e; } .nivel.Alta { color: #b8761b; } .nivel.Critica { color: #a3321f; }

        details.tinta summary { cursor: pointer; font-size: .82rem; color: var(--tinta-suave); margin: .6rem 0 .4rem; }
        details.tinta pre { margin: 0; padding: .9rem; background: #2b1d10; color: #f3e3bd; border-radius: 6px; font-size: .78rem; line-height: 1.5; overflow: auto; max-height: 320px; border: 2px solid #1a0e06; }

        @@keyframes entrar { from { opacity: 0; translate: 0 12px; } to { opacity: 1; translate: 0 0; } }
        @@media (prefers-reduced-motion: reduce) { .cartel { animation: none; } .logpose .aguja { animation: none; } }
        @@media (max-width: 640px) {
            header.proa { padding: 1rem 1.6rem 3.4rem; } main { padding: 0 1.6rem 3rem; }
            .capitan { margin-left: 0; text-align: left; } .dos { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body data-org="{{ auth()->user()->organizacion?->Nombre ?? '' }}">

<header class="proa">
    <div class="franja">
        <span><b>Red Line</b> · Gran Línea · <b>Gobierno Mundial</b></span>
        <span>Marina · Cipher Pol · Gobierno Afiliado</span>
    </div>
    <div class="proa-fila">
        {{-- Log Pose dibujado a mano en SVG: esfera de cristal, anillo y aguja. --}}
        <svg class="logpose" viewBox="0 0 100 100" role="img" aria-label="Log Pose">
            <circle cx="50" cy="50" r="46" fill="#0d2b4d" stroke="#f2b632" stroke-width="4"/>
            <circle cx="50" cy="50" r="38" fill="#e9f3fb" fill-opacity=".18" stroke="#f3e3bd" stroke-width="1.5"/>
            <g stroke="#f3e3bd" stroke-width="1.5" stroke-linecap="round">
                <path d="M50 14v7M50 79v7M14 50h7M79 50h7"/>
            </g>
            <g class="aguja">
                <path d="M50 20 L56 50 L50 56 L44 50 Z" fill="#c8321e"/>
                <path d="M50 80 L56 50 L50 56 L44 50 Z" fill="#f3e3bd"/>
            </g>
            <circle cx="50" cy="50" r="4" fill="#f2b632" stroke="#7a5a2c"/>
            <path d="M24 30 Q 32 18 46 15" fill="none" stroke="#fff" stroke-opacity=".55" stroke-width="3" stroke-linecap="round"/>
        </svg>
        <div class="titulos">
            <h1>Gran <span>Línea</span><br>Registro de Eventos</h1>
            <p>Módulo de Navegación · Panel de pruebas del CRUD de <code>/eventos</code></p>
        </div>
        <div class="capitan">
            <span class="sello-org">{{ auth()->user()->organizacion?->Nombre ?? 'Sin organización' }}</span><br>
            <strong>{{ auth()->user()->Correo }}</strong><br>
            {{ auth()->user()->rol?->Nombre_rol ?? 'Sin rol' }}<br>
            <a href="{{ url('/inicio') }}">← Volver al cuartel</a>
        </div>
    </div>
    <svg class="olas" viewBox="0 0 1200 40" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 22 Q 50 2 100 22 T 200 22 T 300 22 T 400 22 T 500 22 T 600 22 T 700 22 T 800 22 T 900 22 T 1000 22 T 1100 22 T 1200 22 V40 H0 Z"/>
    </svg>
</header>

<main>

    {{-- ============ Mesa del navegante ============ --}}
    <section class="mesa">
        <h2 class="rotulo">🧭 Mesa del navegante</h2>

        <h3 class="sub" style="margin-top:0">Eternal Poses · rumbos rápidos</h3>
        <div class="poses">
            <button type="button" class="pose lectura" data-caso="listar"><i>📜</i><span>Listar todos<small>GET /eventos</small></span></button>
            <button type="button" class="pose lectura" data-caso="ver1"><i>🔭</i><span>Ver evento 1<small>GET /eventos/1</small></span></button>
            <button type="button" class="pose lectura" data-caso="ver9999"><i>🌫️</i><span>Isla perdida<small>404 esperado</small></span></button>
            <button type="button" class="pose escritura" data-caso="crear"><i>🏴‍☠️</i><span>Crear válido<small>POST</small></span></button>
            <button type="button" class="pose escritura" id="chip-editar" data-caso="editar" disabled><i>🖋️</i><span>Editar último<small>PUT</small></span></button>
            <button type="button" class="pose borrar" id="chip-borrar" data-caso="borrar" disabled><i>💥</i><span>Borrar último<small>DELETE</small></span></button>
            <button type="button" class="pose valida" data-caso="sinnombre"><i>❓</i><span>Sin nombre<small>422 esperado</small></span></button>
            <button type="button" class="pose valida" data-caso="tipo999"><i>🌀</i><span>Tipo inexistente<small>422 esperado</small></span></button>
            <button type="button" class="pose valida" data-caso="isla999"><i>🏝️</i><span>Isla inexistente<small>422 esperado</small></span></button>
        </div>
        <div class="ultimo" id="ultimo"></div>

        <h3 class="sub">Trazar el rumbo a mano</h3>
        <div class="metodos">
            <input type="radio" name="metodo" id="m-GET" value="GET" checked><label for="m-GET">GET</label>
            <input type="radio" name="metodo" id="m-POST" value="POST"><label for="m-POST">POST</label>
            <input type="radio" name="metodo" id="m-PUT" value="PUT"><label for="m-PUT">PUT</label>
            <input type="radio" name="metodo" id="m-DELETE" value="DELETE"><label for="m-DELETE">DELETE</label>
        </div>

        <div class="ruta-vista" id="urlVista">GET /eventos</div>

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
            <button type="button" class="zarpar" id="enviar">🐌 Llamar por Den Den Mushi</button>
            <button type="button" class="limpiar" id="limpiar">Limpiar</button>
        </div>
    </section>

    {{-- ============ Bitácora ============ --}}
    <section class="cuaderno">
        <h2 class="rotulo">📖 Bitácora de la Gran Línea</h2>
        <div class="estado" id="estado"></div>
        <div id="mensaje"></div>
        <div id="carteles"></div>
        <div id="vacio" class="vacio">
            <svg viewBox="0 0 100 100" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="50" cy="50" r="38"/><path d="M50 12v14M50 74v14M12 50h14M74 50h14"/><path d="M50 50l12-22-12 10-12 12 12-10z" fill="currentColor"/>
            </svg>
            El Log Pose aún no marca ningún rumbo.<br>Elige un Eternal Pose o traza uno a mano.
        </div>
        <details class="tinta" id="detalle" hidden>
            <summary>Ver el JSON completo</summary>
            <pre id="respuesta"></pre>
        </details>
    </section>

</main>

<script>
    const $ = (id) => document.getElementById(id);
    let ultimoId = null;

    // Tema según la organización del usuario (Marina por defecto).
    (function () {
        const org = (document.body.dataset.org || '').toLowerCase();
        if (org.includes('cipher')) document.body.classList.add('tema-cp');
        else if (org.includes('gobierno')) document.body.classList.add('tema-gobierno');
    })();

    // Tipos de evento (tabla tipos_evento) con un icono para el cartel.
    const TIPOS = {
        1: ['Guerra', '🔥'], 2: ['Batalla', '⚔️'], 3: ['Buster Call', '💥'], 4: ['Ejecución', '⚖️'],
        5: ['Alianza', '🤝'], 6: ['Reunión', '👥'], 7: ['Invasión', '🚩'], 8: ['Rebelión', '✊'],
    };

    // La amenaza se muestra como recompensa en Berries.
    const RECOMPENSA = { Baja: '10.000.000', Media: '100.000.000', Alta: '500.000.000', Critica: '1.500.000.000' };

    function metodoActual() { return document.querySelector('input[name="metodo"]:checked').value; }

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
        $('ultimo').innerHTML = id === null ? '' : 'Último evento anotado en esta travesía: <b>#' + id + '</b>';
    }

    const CASOS = {
        listar:    () => ({ metodo: 'GET' }),
        ver1:      () => ({ metodo: 'GET', id: 1 }),
        ver9999:   () => ({ metodo: 'GET', id: 9999 }),
        crear:     () => ({ metodo: 'POST', nombre: 'Evento de prueba', tipo: 1, isla: 8, amenaza: 'Media', desc: 'Creado desde el panel de la Gran Línea' }),
        editar:    () => ({ metodo: 'PUT', id: ultimoId, nombre: 'Evento editado' }),
        borrar:    () => ({ metodo: 'DELETE', id: ultimoId }),
        sinnombre: () => ({ metodo: 'POST', nombre: '', tipo: 1, isla: 8 }),
        tipo999:   () => ({ metodo: 'POST', nombre: 'X', tipo: 999, isla: 8 }),
        isla999:   () => ({ metodo: 'POST', nombre: 'X', tipo: 1, isla: 999 }),
    };

    // ---------- pintar ----------
    function limpiarRespuesta() {
        $('estado').innerHTML = '';
        $('mensaje').innerHTML = '';
        $('carteles').innerHTML = '';
    }

    function fechaCorta(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        return isNaN(d) ? iso : d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function claseNivel(n) { return (n || '').normalize('NFD').replace(/[̀-ͯ]/g, ''); }

    function el(tag, clase, texto) {
        const e = document.createElement(tag);
        if (clase) e.className = clase;
        if (texto !== undefined) e.textContent = texto;
        return e;
    }

    function cartel(ev) {
        const c = el('article', 'cartel');
        const nivel = claseNivel(ev.Nivel_amenaza);
        if (ev.Nivel_amenaza) c.appendChild(el('span', 'nivel ' + nivel, ev.Nivel_amenaza));

        c.appendChild(el('div', 'wanted', 'WANTED'));

        const [nombreTipo, icono] = TIPOS[ev.ID_tipo_evento] || ['Tipo #' + ev.ID_tipo_evento, '📜'];
        const foto = el('div', 'foto');
        foto.appendChild(el('span', 'ico', icono));
        foto.appendChild(el('span', 'nombre-isla', 'Isla #' + ev.ID_isla));
        c.appendChild(foto);

        c.appendChild(el('div', 'alias', ev.Nombre ?? 'Sin nombre'));
        c.appendChild(el('div', 'tipo', nombreTipo));
        if (ev.Descripcion) c.appendChild(el('p', 'desc', ev.Descripcion));

        const rec = el('div', 'recompensa');
        rec.appendChild(el('small', '', 'Recompensa'));
        rec.appendChild(el('b', '', RECOMPENSA[nivel] || '???'));
        c.appendChild(rec);

        const pie = el('div', 'pie');
        pie.appendChild(el('span', '', 'Evento #' + ev.ID_evento));
        pie.appendChild(el('span', '', ev.Fecha_evento ? fechaCorta(ev.Fecha_evento) : 'Marina'));
        c.appendChild(pie);
        return c;
    }

    function pintarCarteles(eventos) {
        if (!eventos.length) {
            $('carteles').appendChild(el('div', 'vacio', 'No hay carteles en el tablón.'));
            return;
        }
        const cont = el('div', 'carteles');
        eventos.forEach((e, i) => {
            const c = cartel(e);
            c.style.animationDelay = Math.min(i * 40, 400) + 'ms';
            cont.appendChild(c);
        });
        $('carteles').appendChild(cont);
    }

    function pintarNota(clase, titulo, lista) {
        const d = el('div', 'nota ' + clase);
        d.appendChild(el('strong', '', titulo));
        if (lista && lista.length) {
            const ul = el('ul');
            lista.forEach((l) => ul.appendChild(el('li', '', l)));
            d.appendChild(ul);
        }
        $('mensaje').appendChild(d);
    }

    function pintar(res, datos, metodo, url) {
        limpiarRespuesta();
        $('vacio').hidden = true;

        const grupo = Math.floor(res.status / 100);
        $('estado').appendChild(el('span', 'lacre c' + (grupo === 2 ? '2' : grupo === 5 ? '5' : '4'), String(res.status)));
        const ruta = el('span', 'ruta-resp', metodo + ' ' + url);
        ruta.appendChild(el('small', '', 'Respuesta del Den Den Mushi'));
        $('estado').appendChild(ruta);

        if (Array.isArray(datos)) {
            pintarCarteles(datos);
        } else if (res.ok) {
            pintarNota('ok', datos.message || 'Operación correcta');
            if (datos.data && datos.data.ID_evento) pintarCarteles([datos.data]);
            else if (datos.ID_evento) pintarCarteles([datos]);
        } else if (res.status === 403) {
            pintarNota('error', 'Cipher Pol te cierra el paso (403)', ['Tu rol no tiene permiso para esta acción.']);
        } else if (res.status === 404) {
            pintarNota('aviso', 'Esa isla no aparece en el Log Pose (404)', ['El evento no existe o ya fue borrado.']);
        } else if (res.status === 422 && datos.errors) {
            const lista = [];
            for (const [campo, msgs] of Object.entries(datos.errors)) msgs.forEach((m) => lista.push(campo + ': ' + m));
            pintarNota('aviso', 'La Marina rechaza el informe (422)', lista);
        } else if (res.status === 401 || res.status === 419) {
            pintarNota('aviso', 'Sesión no válida', ['Vuelve a iniciar sesión y recarga esta página.']);
        } else {
            pintarNota('error', datos.message || 'Error del servidor');
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
        for (const [k, v] of Object.entries(campos)) { if (v !== '') cuerpo[k] = v; }

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

            if (metodo === 'POST' && res.status === 201 && datos.data) marcarUltimo(datos.data.ID_evento);
            if (metodo === 'DELETE' && res.ok && String(ultimoId) === id) marcarUltimo(null);
        } catch (e) {
            limpiarRespuesta();
            $('vacio').hidden = true;
            pintarNota('error', 'Se perdió la señal del Den Den Mushi', [String(e)]);
        } finally {
            $('enviar').disabled = false;
        }
    }

    // ---------- eventos de la página ----------
    document.querySelectorAll('.pose').forEach((b) => {
        b.addEventListener('click', () => { llenar(CASOS[b.dataset.caso]()); enviar(); });
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