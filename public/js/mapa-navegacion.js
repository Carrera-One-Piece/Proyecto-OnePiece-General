/*
 * Mapa navegable de la Grand Line (RF10) — Módulo de Navegación (RF11-RF12).
 *
 * Lo usa resources/views/mapa.blade.php. No necesita Vite: Leaflet viene del
 * CDN y este archivo vive en public/js.
 *
 * Coordenadas: las de la base de datos (tabla isla, ubicacion_actual...):
 *   X de 0 a 1500 (izquierda a derecha), Y de 0 a 1000 (de arriba hacia abajo).
 * Leaflet (CRS.Simple) usa [lat, lng] con lat hacia arriba, así que se
 * convierte con aLatLng([x, y]) = [ALTO - y, x].
 */
(function () {
    'use strict';

    const carta = document.getElementById('carta');
    if (!carta || typeof L === 'undefined') {
        return;
    }

    const cfg = {
        datos: carta.dataset.datos,
        ubicar: carta.dataset.ubicar,
        recorrido: carta.dataset.recorrido,
        predicciones: carta.dataset.predicciones,
        predecir: carta.dataset.predecir,
        csrf: carta.dataset.csrf,
        puedeEditar: carta.dataset.puedeEditar === '1',
        puedePredecir: carta.dataset.puedePredecir === '1',
    };

    let ANCHO = 1500;
    let ALTO = 1000;

    // Colores por mar (ID_mar de la tabla mares). Los que no estén usan el gris.
    const COLOR_MAR = {
        1: '#4fa3d9', // East Blue
        2: '#5c8fd6', // West Blue
        3: '#6aa0c9', // North Blue
        4: '#4fb4c4', // South Blue
        5: '#e3b552', // Grand Line
        6: '#7fb08f', // Calm Belt
        7: '#e0645c', // New World
    };
    const COLOR_NIVEL = {
        'crítica': '#ff3b30', 'critica': '#ff3b30', 'crítico': '#ff3b30',
        'alta': '#ff8a3d', 'media': '#ffd23f', 'baja': '#62c370',
    };
    const COLORES_RUTA = ['#ffd166', '#ef476f', '#06d6a0', '#8ecae6', '#f78c6b', '#c77dff', '#90be6d', '#f9c74f'];

    const aLatLng = ([x, y]) => [ALTO - y, x];
    const aXY = (latlng) => [Math.round(latlng.lng * 100) / 100, Math.round((ALTO - latlng.lat) * 100) / 100];

    const $ = (sel, raiz = document) => raiz.querySelector(sel);
    const $$ = (sel, raiz = document) => Array.from(raiz.querySelectorAll(sel));
    const esc = (t) => String(t ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fecha = (f) => {
        if (!f) return '—';
        const d = new Date(String(f).replace(' ', 'T'));
        return isNaN(d) ? esc(f) : d.toLocaleDateString('es-CO', { year: 'numeric', month: 'short', day: 'numeric' });
    };
    const berries = (v) => v == null ? '—' : '฿ ' + Number(v).toLocaleString('es-CO');
    const nombreMar = (id) => (estado.datos?.mares || []).find((m) => m.id === id)?.nombre || 'Mar desconocido';

    // ------------------------------------------------------------------ mapa

    const mapa = L.map('mapa', {
        crs: L.CRS.Simple,
        minZoom: -1.5,
        maxZoom: 3,
        zoomSnap: 0.25,
        zoomDelta: 0.5,
        wheelPxPerZoomLevel: 90,
        attributionControl: false,
        zoomControl: true,
    });

    const capas = {
        islas: L.layerGroup(),
        conexiones: L.layerGroup(),
        eventos: L.layerGroup(),
        tripulaciones: L.layerGroup(),
        otros: L.layerGroup(),
        recorridos: L.layerGroup(),
        seleccion: L.layerGroup(),   // recorrido / predicción de lo seleccionado
        edicion: L.layerGroup(),     // punto que se está ubicando
    };

    const estado = {
        datos: null,
        marcadoresIsla: new Map(),
        marcadoresTripulacion: new Map(),
        eligiendoPunto: false,
        fondo: null,
    };

    // -------------------------------------------------------- fondo náutico

    /**
     * Carta náutica ilustrada, dibujada en SVG con las coordenadas de la BD
     * (viewBox = 0..ANCHO x 0..ALTO). Todo es original del proyecto, no una
     * imagen de la serie:
     *   océano con textura y profundidad, corrientes animadas en la Grand Line,
     *   Calm Belt, la Red Line como muro de roca, cada isla dibujada según su
     *   tipo (nieve, desierto, volcán, nube, submarina, archipiélago...),
     *   marco de carta antigua con graduación, escala y rosa de los vientos.
     */

    // Generador pseudoaleatorio con semilla: cada isla sale siempre igual.
    function azar(semilla) {
        let a = semilla >>> 0;
        return () => {
            a = (a + 0x6D2B79F5) >>> 0;
            let t = a;
            t = Math.imul(t ^ (t >>> 15), t | 1);
            t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
            return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
        };
    }

    // Contorno orgánico cerrado (curvas suaves) alrededor de (cx, cy).
    function contorno(cx, cy, rx, ry, rnd, puntos = 10, irregular = 0.28, giro = 0) {
        const p = [];
        for (let i = 0; i < puntos; i++) {
            const a = giro + (i / puntos) * Math.PI * 2;
            const f = 1 - irregular / 2 + rnd() * irregular;
            p.push([cx + Math.cos(a) * rx * f, cy + Math.sin(a) * ry * f]);
        }
        // Catmull-Rom -> Bézier
        let d = `M${p[0][0].toFixed(1)},${p[0][1].toFixed(1)}`;
        for (let i = 0; i < puntos; i++) {
            const p0 = p[(i - 1 + puntos) % puntos];
            const p1 = p[i];
            const p2 = p[(i + 1) % puntos];
            const p3 = p[(i + 2) % puntos];
            const c1 = [p1[0] + (p2[0] - p0[0]) / 6, p1[1] + (p2[1] - p0[1]) / 6];
            const c2 = [p2[0] - (p3[0] - p1[0]) / 6, p2[1] - (p3[1] - p1[1]) / 6];
            d += ` C${c1[0].toFixed(1)},${c1[1].toFixed(1)} ${c2[0].toFixed(1)},${c2[1].toFixed(1)} ${p2[0].toFixed(1)},${p2[1].toFixed(1)}`;
        }
        return d + 'Z';
    }

    /** Qué "bioma" dibujar según el Tipo de la isla. */
    function biomaDe(isla) {
        const t = `${isla.tipo || ''} ${isla.nombre || ''}`.toLowerCase();
        if (/cielo|sky/.test(t)) return 'nube';
        if (/submarin|hombres pez/.test(t)) return 'submarina';
        if (/restaurante|flotante/.test(t)) return 'flotante';
        if (/invierno|nieve|drum/.test(t)) return 'nieve';
        if (/desért|desert|arena/.test(t)) return 'desierto';
        if (/volc|punk/.test(t)) return 'volcan';
        if (/monta|paso mar|reverse/.test(t)) return 'montana';
        if (/barco|thriller/.test(t)) return 'sombria';
        if (/archipi/.test(t)) return 'archipielago';
        if (/alargad|long ring/.test(t)) return 'alargada';
        if (/prisi|gubernament|marina|base/.test(t)) return 'fortaleza';
        if (/científ|cientif|egghead/.test(t)) return 'futurista';
        if (/gigante|elbaf|elefante|zou/.test(t)) return 'bosque';
        return 'verde';
    }

    /** Tamaño base de cada isla (en unidades del mapa). */
    function tamanoDe(isla) {
        const t = (isla.tipo || '').toLowerCase();
        if (/reino|país|pais|territorio yonko|ciudad acuática|isla de gigantes|isla elefante/.test(t)) return 22;
        if (/ciudad|isla|archipi|base|prisi|gubernament/.test(t)) return 17;
        if (/restaurante/.test(t)) return 8;
        return 12;
    }

    function dibujarIsla(isla) {
        const [x, y] = isla.coords;
        const rnd = azar(isla.id * 7919 + 17);
        const r = tamanoDe(isla);
        const bioma = biomaDe(isla);
        const id = `i${isla.id}`;
        let tierra = '';

        const relleno = {
            verde: 'url(#g-verde)', bosque: 'url(#g-bosque)', nieve: 'url(#g-nieve)', desierto: 'url(#g-desierto)',
            volcan: 'url(#g-volcan)', montana: 'url(#g-montana)', sombria: 'url(#g-sombria)', archipielago: 'url(#g-verde)',
            alargada: 'url(#g-verde)', fortaleza: 'url(#g-roca-isla)', futurista: 'url(#g-futurista)', flotante: 'url(#g-madera)',
        }[bioma];

        if (bioma === 'nube') {
            // Isla del cielo: nubes sobre el mar, con su sombra abajo.
            let nubes = '';
            for (let i = 0; i < 6; i++) {
                const a = (i / 6) * Math.PI * 2;
                nubes += `<circle cx="${(x + Math.cos(a) * r * 0.75).toFixed(1)}" cy="${(y + Math.sin(a) * r * 0.45).toFixed(1)}" r="${(r * (0.45 + rnd() * 0.25)).toFixed(1)}"/>`;
            }
            return `
                <ellipse cx="${x + 4}" cy="${y + 10}" rx="${r * 1.3}" ry="${r * 0.5}" fill="#02121f" opacity=".35" filter="url(#f-desenfoque)"/>
                <g fill="url(#g-nube)" stroke="#ffffff" stroke-opacity=".6" stroke-width=".6" filter="url(#f-sombra)">
                    ${nubes}<ellipse cx="${x}" cy="${y}" rx="${r * 1.05}" ry="${r * 0.6}"/>
                </g>
                <path d="${contorno(x, y - 1, r * 0.55, r * 0.3, rnd, 8, 0.3)}" fill="url(#g-verde)" opacity=".9"/>`;
        }

        if (bioma === 'submarina') {
            // Bajo el mar: remolino oscuro con burbujas.
            let burbujas = '';
            for (let i = 0; i < 7; i++) {
                burbujas += `<circle cx="${(x + (rnd() - 0.5) * r * 2.2).toFixed(1)}" cy="${(y + (rnd() - 0.5) * r * 2.2).toFixed(1)}" r="${(0.8 + rnd() * 1.6).toFixed(1)}"/>`;
            }
            return `
                <circle cx="${x}" cy="${y}" r="${r * 1.6}" fill="url(#g-abismo)"/>
                <g fill="none" stroke="#9fe3ef" stroke-linecap="round">
                    <path d="M${x - r * 1.2},${y} a${r * 1.2},${r * 1.2} 0 1,1 ${r * 1.2},${r * 1.2}" stroke-opacity=".45" stroke-width="1.2" class="giro"/>
                    <path d="M${x + r * 0.7},${y} a${r * 0.7},${r * 0.7} 0 1,1 -${r * 0.7},-${r * 0.7}" stroke-opacity=".6" stroke-width="1" class="giro giro--rapido"/>
                </g>
                <g fill="#d6f6ff" fill-opacity=".55">${burbujas}</g>`;
        }

        const formas = [];
        if (bioma === 'archipielago') {
            const n = 4 + Math.floor(rnd() * 3);
            for (let i = 0; i < n; i++) {
                const a = rnd() * Math.PI * 2;
                const d = r * (0.3 + rnd() * 0.9);
                formas.push([x + Math.cos(a) * d, y + Math.sin(a) * d, r * (0.25 + rnd() * 0.25), r * (0.2 + rnd() * 0.2)]);
            }
        } else if (bioma === 'alargada') {
            formas.push([x, y, r * 2.3, r * 0.45]);
        } else if (bioma === 'flotante') {
            formas.push([x, y, r * 1.2, r * 0.55]);
        } else {
            formas.push([x, y, r * (0.95 + rnd() * 0.2), r * (0.75 + rnd() * 0.25)]);
            if (r >= 13 && rnd() > 0.35) {
                const a = rnd() * Math.PI * 2;
                formas.push([x + Math.cos(a) * r * 1.25, y + Math.sin(a) * r * 1.0, r * 0.32, r * 0.26]);
            }
        }

        const giro = rnd() * Math.PI;
        const caminos = formas.map(([fx, fy, rx, ry], i) => contorno(fx, fy, rx, ry, azar(isla.id * 31 + i), bioma === 'alargada' ? 14 : 10, 0.32, giro));
        const playas = formas.map(([fx, fy, rx, ry], i) => contorno(fx, fy, rx + 2.4, ry + 2.4, azar(isla.id * 31 + i), bioma === 'alargada' ? 14 : 10, 0.32, giro));
        const bajos = formas.map(([fx, fy, rx, ry]) => `<ellipse cx="${fx}" cy="${fy}" rx="${rx * 1.9 + 6}" ry="${ry * 1.9 + 6}"/>`).join('');

        tierra += `<g fill="#7fe0e0" opacity=".22" filter="url(#f-desenfoque)">${bajos}</g>`;
        tierra += `<g fill="none" stroke="#bff3f0" stroke-opacity=".35" stroke-width=".8">${playas.map((d) => `<path d="${d}" transform="translate(0 0) scale(1)" />`).join('')}</g>`;
        tierra += `<g fill="#ead7a2" filter="url(#f-sombra)">${playas.map((d) => `<path d="${d}"/>`).join('')}</g>`;
        tierra += `<g fill="${relleno}" filter="url(#f-relieve)">${caminos.map((d) => `<path d="${d}"/>`).join('')}</g>`;

        // Detalles según el bioma.
        if (bioma === 'volcan') {
            tierra += `<circle cx="${x}" cy="${y}" r="${r * 0.28}" fill="#ff6a2b" filter="url(#f-brillo)" class="lava"/>
                       <path d="M${x - r * 0.3},${y - r * 0.2} q${r * 0.15},-${r * 0.9} ${r * 0.4},-${r * 1.4}" stroke="#9aa3a8" stroke-opacity=".5" stroke-width="2.4" fill="none" stroke-linecap="round" filter="url(#f-desenfoque)"/>`;
        } else if (bioma === 'montana' || bioma === 'nieve') {
            const pico = (px, py, s) => `<path d="M${px - s},${py + s * 0.6} L${px},${py - s} L${px + s},${py + s * 0.6}Z" fill="${bioma === 'nieve' ? '#9fb3c4' : '#6e5038'}"/>
                                          <path d="M${px - s * 0.35},${py - s * 0.35} L${px},${py - s} L${px + s * 0.35},${py - s * 0.35} L${px + s * 0.1},${py - s * 0.2} L${px - s * 0.1},${py - s * 0.3}Z" fill="#ffffff"/>`;
            tierra += pico(x - r * 0.3, y, r * 0.42) + pico(x + r * 0.25, y - r * 0.1, r * 0.55);
        } else if (bioma === 'bosque' || bioma === 'verde') {
            let arboles = '';
            const n = bioma === 'bosque' ? 7 : Math.max(2, Math.round(r / 4));
            for (let i = 0; i < n; i++) {
                const a = rnd() * Math.PI * 2;
                const d = rnd() * r * 0.55;
                arboles += `<circle cx="${(x + Math.cos(a) * d).toFixed(1)}" cy="${(y + Math.sin(a) * d * 0.8).toFixed(1)}" r="${(r * (0.12 + rnd() * 0.1)).toFixed(1)}"/>`;
            }
            tierra += `<g fill="#2f5a2a" opacity=".75">${arboles}</g>`;
        } else if (bioma === 'desierto') {
            tierra += `<g fill="none" stroke="#a9773b" stroke-opacity=".55" stroke-width="1" stroke-linecap="round">
                <path d="M${x - r * 0.6},${y - r * 0.1} q${r * 0.3},-${r * 0.25} ${r * 0.6},0 t${r * 0.6},0"/>
                <path d="M${x - r * 0.4},${y + r * 0.3} q${r * 0.25},-${r * 0.2} ${r * 0.5},0 t${r * 0.5},0"/></g>`;
        } else if (bioma === 'fortaleza') {
            const s = r * 0.32;
            tierra += `<g fill="#e9eef2" stroke="#5c6b77" stroke-width=".7">
                <rect x="${x - s * 1.3}" y="${y - s * 0.6}" width="${s * 2.6}" height="${s * 1.4}"/>
                <rect x="${x - s * 0.4}" y="${y - s * 1.5}" width="${s * 0.8}" height="${s * 1.1}"/>
                <path d="M${x - s * 0.5},${y - s * 1.5} L${x},${y - s * 2} L${x + s * 0.5},${y - s * 1.5}Z" fill="#3b6ea5"/></g>`;
        } else if (bioma === 'futurista') {
            tierra += `<g fill="none" stroke="#9ef3ff" stroke-width="1.1" filter="url(#f-brillo)">
                <circle cx="${x}" cy="${y}" r="${r * 0.35}"/><circle cx="${x}" cy="${y}" r="${r * 0.6}" stroke-dasharray="2 3"/></g>`;
        } else if (bioma === 'sombria') {
            tierra += `<g fill="#1a1024" opacity=".85"><path d="M${x - r * 0.3},${y + r * 0.3} l${r * 0.15},-${r * 0.9} l${r * 0.15},${r * 0.9}Z"/>
                       <path d="M${x + r * 0.05},${y + r * 0.35} l${r * 0.12},-${r * 0.7} l${r * 0.12},${r * 0.7}Z"/></g>
                       <circle cx="${x}" cy="${y}" r="${r * 1.6}" fill="#3a2350" opacity=".18" filter="url(#f-desenfoque)"/>`;
        } else if (bioma === 'flotante') {
            tierra += `<path d="M${x - r * 0.6},${y} L${x + r * 0.6},${y}" stroke="#f2e2bd" stroke-width="1"/>`;
        }

        return `<g id="${id}">${tierra}</g>`;
    }

    function crearFondo(islas = []) {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
        svg.setAttribute('viewBox', `0 0 ${ANCHO} ${ALTO}`);

        // Graduación del marco (barras alternas como en las cartas antiguas).
        let graduacion = '';
        for (let x = 0; x < ANCHO; x += 50) {
            const c = (x / 50) % 2 ? '#e9d7a8' : '#2a1d10';
            graduacion += `<rect x="${x}" y="0" width="50" height="7" fill="${c}"/><rect x="${x}" y="${ALTO - 7}" width="50" height="7" fill="${c}"/>`;
        }
        for (let y = 0; y < ALTO; y += 50) {
            const c = (y / 50) % 2 ? '#2a1d10' : '#e9d7a8';
            graduacion += `<rect x="0" y="${y}" width="7" height="50" fill="${c}"/><rect x="${ANCHO - 7}" y="${y}" width="7" height="50" fill="${c}"/>`;
        }

        let rejilla = '';
        for (let x = 100; x < ANCHO; x += 100) {
            rejilla += `<line x1="${x}" y1="10" x2="${x}" y2="${ALTO - 10}"/><text x="${x}" y="24" text-anchor="middle">${x}</text>`;
        }
        for (let y = 100; y < ALTO; y += 100) {
            rejilla += `<line x1="10" y1="${y}" x2="${ANCHO - 10}" y2="${y}"/><text x="26" y="${y + 4}" text-anchor="middle">${y}</text>`;
        }

        // Red Line: muro de roca de norte a sur, con borde irregular.
        const redLine = (x0, semilla) => {
            const rnd = azar(semilla);
            let izq = [];
            let der = [];
            for (let y = -30; y <= ALTO + 30; y += 25) {
                izq.push([x0 - 15 - rnd() * 6 + Math.sin(y / 60 + semilla) * 5, y]);
                der.push([x0 + 15 + rnd() * 6 + Math.cos(y / 47 + semilla) * 5, y]);
            }
            const pts = [...izq, ...der.reverse()];
            return `M${pts.map((p) => p.map((n) => n.toFixed(1)).join(',')).join(' L')}Z`;
        };

        // Corrientes de la Grand Line (líneas que fluyen hacia el este).
        let corrientes = '';
        const rc = azar(42);
        for (let i = 0; i < 16; i++) {
            const y = 140 + rc() * 600;
            const x = 420 + rc() * 950;
            const l = 60 + rc() * 120;
            corrientes += `<path d="M${x.toFixed(0)},${y.toFixed(0)} q${(l / 2).toFixed(0)},-8 ${l.toFixed(0)},0" style="animation-delay:-${(rc() * 6).toFixed(1)}s"/>`;
        }

        // Olitas sueltas en el mar.
        let olas = '';
        const ro = azar(7);
        for (let i = 0; i < 70; i++) {
            const x = 20 + ro() * (ANCHO - 40);
            const y = 20 + ro() * (ALTO - 40);
            olas += `<path d="M${x.toFixed(0)},${y.toFixed(0)} q4,-4 8,0 q4,-4 8,0"/>`;
        }

        svg.innerHTML = `
            <defs>
                <radialGradient id="g-oceano" cx="52%" cy="46%" r="75%">
                    <stop offset="0" stop-color="#14506a"/>
                    <stop offset=".4" stop-color="#0f415a"/>
                    <stop offset=".75" stop-color="#0a2f45"/>
                    <stop offset="1" stop-color="#061d2e"/>
                </radialGradient>
                <radialGradient id="g-vineta" cx="50%" cy="50%" r="70%">
                    <stop offset=".6" stop-color="#000" stop-opacity="0"/>
                    <stop offset="1" stop-color="#020b14" stop-opacity=".55"/>
                </radialGradient>
                <linearGradient id="g-grandline" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="#5fd4e6" stop-opacity="0"/>
                    <stop offset=".12" stop-color="#5fd4e6" stop-opacity=".12"/>
                    <stop offset=".5" stop-color="#7ee3ef" stop-opacity=".16"/>
                    <stop offset=".88" stop-color="#5fd4e6" stop-opacity=".12"/>
                    <stop offset="1" stop-color="#5fd4e6" stop-opacity="0"/>
                </linearGradient>
                <linearGradient id="g-roca" x1="0" x2="1">
                    <stop offset="0" stop-color="#4a1f18"/>
                    <stop offset=".3" stop-color="#8e3b2b"/>
                    <stop offset=".55" stop-color="#b85a3f"/>
                    <stop offset=".8" stop-color="#7a3125"/>
                    <stop offset="1" stop-color="#3f1a14"/>
                </linearGradient>
                <radialGradient id="g-verde"><stop offset="0" stop-color="#8fbf63"/><stop offset=".7" stop-color="#5f9246"/><stop offset="1" stop-color="#4a7a37"/></radialGradient>
                <radialGradient id="g-bosque"><stop offset="0" stop-color="#6fa154"/><stop offset="1" stop-color="#2f5d2c"/></radialGradient>
                <radialGradient id="g-nieve"><stop offset="0" stop-color="#ffffff"/><stop offset=".7" stop-color="#dfe9f2"/><stop offset="1" stop-color="#b9cbdb"/></radialGradient>
                <radialGradient id="g-desierto"><stop offset="0" stop-color="#f3d58e"/><stop offset=".7" stop-color="#ddb169"/><stop offset="1" stop-color="#c18e48"/></radialGradient>
                <radialGradient id="g-volcan"><stop offset="0" stop-color="#7b3b26"/><stop offset=".55" stop-color="#4a2a22"/><stop offset="1" stop-color="#2b1b17"/></radialGradient>
                <radialGradient id="g-montana"><stop offset="0" stop-color="#a98a63"/><stop offset="1" stop-color="#6d5238"/></radialGradient>
                <radialGradient id="g-sombria"><stop offset="0" stop-color="#5a4a6a"/><stop offset="1" stop-color="#2a2236"/></radialGradient>
                <radialGradient id="g-roca-isla"><stop offset="0" stop-color="#a7b0a0"/><stop offset="1" stop-color="#5f6f5a"/></radialGradient>
                <radialGradient id="g-futurista"><stop offset="0" stop-color="#cfe9e6"/><stop offset="1" stop-color="#6fa3a0"/></radialGradient>
                <radialGradient id="g-madera"><stop offset="0" stop-color="#b98a5a"/><stop offset="1" stop-color="#6e4a2b"/></radialGradient>
                <radialGradient id="g-nube"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#dfe8f0"/></radialGradient>
                <radialGradient id="g-abismo"><stop offset="0" stop-color="#03111c" stop-opacity=".9"/><stop offset=".7" stop-color="#062639" stop-opacity=".6"/><stop offset="1" stop-color="#0d3b55" stop-opacity="0"/></radialGradient>

                <filter id="f-textura" x="0" y="0" width="100%" height="100%">
                    <feTurbulence type="fractalNoise" baseFrequency=".012 .03" numOctaves="3" seed="4"/>
                    <feColorMatrix values="0 0 0 0 .45  0 0 0 0 .78  0 0 0 0 .9  0 0 0 .07 0"/>
                </filter>
                <filter id="f-roca" x="-20%" y="0" width="140%" height="100%">
                    <feTurbulence type="fractalNoise" baseFrequency=".09" numOctaves="3" seed="9" result="ruido"/>
                    <feDisplacementMap in="SourceGraphic" in2="ruido" scale="9"/>
                </filter>
                <filter id="f-grieta" x="0" y="0" width="100%" height="100%">
                    <feTurbulence type="fractalNoise" baseFrequency=".15 .04" numOctaves="2" seed="3"/>
                    <feColorMatrix values="0 0 0 0 .1  0 0 0 0 .03  0 0 0 0 .02  0 0 0 -2.4 1.2"/>
                    <feComposite in2="SourceGraphic" operator="in"/>
                </filter>
                <filter id="f-sombra" x="-40%" y="-40%" width="180%" height="180%">
                    <feDropShadow dx="1.5" dy="2.5" stdDeviation="1.8" flood-color="#021019" flood-opacity=".55"/>
                </filter>
                <filter id="f-relieve" x="-20%" y="-20%" width="140%" height="140%">
                    <feTurbulence type="fractalNoise" baseFrequency=".35" numOctaves="2" seed="2" result="n"/>
                    <feDiffuseLighting in="n" surfaceScale="1.6" lighting-color="#fff4dc" result="luz"><feDistantLight azimuth="225" elevation="55"/></feDiffuseLighting>
                    <feComposite in="luz" in2="SourceGraphic" operator="in" result="luzRec"/>
                    <feBlend in="SourceGraphic" in2="luzRec" mode="multiply"/>
                </filter>
                <filter id="f-desenfoque" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="4"/></filter>
                <filter id="f-brillo" x="-100%" y="-100%" width="300%" height="300%">
                    <feGaussianBlur stdDeviation="2" result="b"/><feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
                </filter>
                <pattern id="p-estratos" width="40" height="14" patternUnits="userSpaceOnUse">
                    <path d="M0 4 q10 -2 20 0 t20 0" fill="none" stroke="#3a120c" stroke-opacity=".55" stroke-width="1.4"/>
                    <path d="M0 10 q10 2 20 0 t20 0" fill="none" stroke="#e39a73" stroke-opacity=".25" stroke-width=".8"/>
                </pattern>
                <pattern id="p-calma" width="18" height="18" patternUnits="userSpaceOnUse" patternTransform="rotate(-20)">
                    <path d="M0 9 q4.5 -3 9 0 t9 0" fill="none" stroke="#bfe8d0" stroke-opacity=".14" stroke-width="1"/>
                </pattern>
            </defs>

            <!-- Océano -->
            <rect width="${ANCHO}" height="${ALTO}" fill="url(#g-oceano)"/>
            <rect width="${ANCHO}" height="${ALTO}" filter="url(#f-textura)"/>

            <!-- Grand Line: corriente entre los dos Calm Belt -->
            <rect x="395" y="95" width="${ANCHO - 395}" height="680" fill="url(#g-grandline)"/>
            <g fill="none" stroke="#cdf6ff" stroke-opacity=".28" stroke-width="1.4" stroke-linecap="round" class="corrientes">${corrientes}</g>

            <!-- Calm Belt: mar en calma, plano y verdoso -->
            <rect x="395" y="775" width="${ANCHO - 395}" height="165" fill="#2c6f6a" fill-opacity=".28"/>
            <rect x="395" y="775" width="${ANCHO - 395}" height="165" fill="url(#p-calma)"/>
            <rect x="395" y="20" width="${ANCHO - 395}" height="72" fill="#2c6f6a" fill-opacity=".28"/>
            <rect x="395" y="20" width="${ANCHO - 395}" height="72" fill="url(#p-calma)"/>
            <g stroke="#e9d7a8" stroke-opacity=".28" stroke-dasharray="2 7" stroke-linecap="round" stroke-width="1.5">
                <line x1="395" y1="95" x2="${ANCHO}" y2="95"/><line x1="395" y1="775" x2="${ANCHO}" y2="775"/>
            </g>

            <!-- Olitas -->
            <g fill="none" stroke="#d8f3ff" stroke-opacity=".16" stroke-width="1" stroke-linecap="round">${olas}</g>

            <!-- Cuadrícula de navegación -->
            <g stroke="#e9d7a8" stroke-opacity=".06" fill="#e9d7a8" fill-opacity=".38"
               font-size="9" font-family="'IM Fell English', Georgia, serif">${rejilla}</g>

            <!-- Red Line -->
            <g>
                <path d="${redLine(382, 1)}" fill="#020b14" opacity=".45" transform="translate(7 4)" filter="url(#f-desenfoque)"/>
                <path d="${redLine(812, 2)}" fill="#020b14" opacity=".45" transform="translate(7 4)" filter="url(#f-desenfoque)"/>
                <g filter="url(#f-roca)">
                    <path d="${redLine(382, 1)}" fill="url(#g-roca)"/>
                    <path d="${redLine(812, 2)}" fill="url(#g-roca)"/>
                </g>
                <g filter="url(#f-roca)">
                    <path d="${redLine(382, 1)}" fill="url(#p-estratos)"/>
                    <path d="${redLine(812, 2)}" fill="url(#p-estratos)"/>
                </g>
                <g filter="url(#f-roca)" opacity=".55">
                    <path d="${redLine(382, 1)}" fill="#000" filter="url(#f-grieta)"/>
                    <path d="${redLine(812, 2)}" fill="#000" filter="url(#f-grieta)"/>
                </g>
                <g fill="none" stroke="#f3c7a3" stroke-opacity=".35" stroke-width="1.2" filter="url(#f-roca)">
                    <path d="${redLine(382, 1)}"/><path d="${redLine(812, 2)}"/>
                </g>
            </g>

            <!-- Islas -->
            <g class="islas-dibujadas">${islas.map(dibujarIsla).join('')}</g>

            <!-- Nombres de los mares -->
            <g font-family="'Pirata One', 'IM Fell English', Georgia, serif" fill="#f6e7bf" text-anchor="middle">
                <text x="382" y="300" font-size="17" letter-spacing="12" fill-opacity=".8" transform="rotate(-90 382 300)">RED LINE</text>
                <text x="812" y="690" font-size="17" letter-spacing="12" fill-opacity=".8" transform="rotate(-90 812 690)">RED LINE</text>
                <text x="190" y="480" font-size="44" letter-spacing="10" fill-opacity=".30">East Blue</text>
                <text x="600" y="150" font-size="36" letter-spacing="10" fill-opacity=".32">Grand Line</text>
                <text x="600" y="174" font-size="13" letter-spacing="9" fill-opacity=".35" font-family="'IM Fell English', Georgia, serif" font-style="italic">— Paraíso —</text>
                <text x="1165" y="425" font-size="48" letter-spacing="14" fill-opacity=".26">New World</text>
                <g font-family="'IM Fell English', Georgia, serif" font-style="italic" font-size="17" letter-spacing="10" fill-opacity=".38">
                    <text x="600" y="930">Calm Belt</text><text x="1150" y="930">Calm Belt</text>
                </g>
            </g>

            <!-- Rosa de los vientos -->
            <g transform="translate(1395 845)">
                <circle r="62" fill="#04131f" fill-opacity=".35" stroke="#e9d7a8" stroke-opacity=".45"/>
                <circle r="54" fill="none" stroke="#e9d7a8" stroke-opacity=".25" stroke-dasharray="1 4"/>
                <g fill="#e9d7a8" fill-opacity=".35" transform="rotate(45)">
                    <polygon points="0,-44 5,-5 0,0 -5,-5"/><polygon points="0,44 5,5 0,0 -5,5"/>
                    <polygon points="44,0 5,5 0,0 5,-5"/><polygon points="-44,0 -5,5 0,0 -5,-5"/>
                </g>
                <polygon points="0,-60 8,-8 0,0" fill="#f6e7bf"/><polygon points="0,-60 -8,-8 0,0" fill="#b9a26f"/>
                <polygon points="0,60 8,8 0,0" fill="#b9a26f"/><polygon points="0,60 -8,8 0,0" fill="#f6e7bf"/>
                <polygon points="60,0 8,8 0,0" fill="#f6e7bf"/><polygon points="60,0 8,-8 0,0" fill="#b9a26f"/>
                <polygon points="-60,0 -8,8 0,0" fill="#b9a26f"/><polygon points="-60,0 -8,-8 0,0" fill="#f6e7bf"/>
                <circle r="5" fill="#b8322b" stroke="#f6e7bf"/>
                <g font-family="'Pirata One', Georgia, serif" font-size="15" fill="#f6e7bf" text-anchor="middle">
                    <text y="-67">N</text><text y="79">S</text><text x="73" y="5">E</text><text x="-73" y="5">O</text>
                </g>
            </g>

            <!-- Escala -->
            <g transform="translate(1080 955)" font-family="'IM Fell English', Georgia, serif" font-size="10" fill="#e9d7a8">
                <rect width="50" height="5" fill="#e9d7a8"/><rect x="50" width="50" height="5" fill="#2a1d10" stroke="#e9d7a8" stroke-width=".6"/>
                <rect x="100" width="50" height="5" fill="#e9d7a8"/><rect x="150" width="50" height="5" fill="#2a1d10" stroke="#e9d7a8" stroke-width=".6"/>
                <text y="-5">0</text><text x="100" y="-5" text-anchor="middle">100</text><text x="200" y="-5" text-anchor="middle">200 leguas</text>
            </g>

            <!-- Viñeta y marco de carta antigua -->
            <rect width="${ANCHO}" height="${ALTO}" fill="url(#g-vineta)" pointer-events="none"/>
            <g>${graduacion}</g>
            <rect x="7" y="7" width="${ANCHO - 14}" height="${ALTO - 14}" fill="none" stroke="#e9d7a8" stroke-width="2"/>
            <rect x="12" y="12" width="${ANCHO - 24}" height="${ALTO - 24}" fill="none" stroke="#e9d7a8" stroke-opacity=".45" stroke-width=".8"/>
            <g fill="#e9d7a8">
                ${[[12, 12], [ANCHO - 12, 12], [12, ALTO - 12], [ANCHO - 12, ALTO - 12]].map(([cx, cy]) => `<rect x="${cx - 5}" y="${cy - 5}" width="10" height="10" transform="rotate(45 ${cx} ${cy})"/>`).join('')}
            </g>
        `;

        return L.svgOverlay(svg, [[0, 0], [ALTO, ANCHO]], { interactive: false, className: 'carta-fondo' });
    }

    // ------------------------------------------------------------- controles

    const coordenadas = L.control({ position: 'bottomleft' });
    coordenadas.onAdd = () => {
        const div = L.DomUtil.create('div', 'carta-coordenadas');
        div.textContent = 'X — · Y —';
        return div;
    };

    mapa.on('mousemove', (e) => {
        const [x, y] = aXY(e.latlng);
        const div = $('.carta-coordenadas');
        if (div) div.textContent = `X ${Math.round(x)} · Y ${Math.round(y)}`;
    });

    function crearLeyenda(mares) {
        const leyenda = L.control({ position: 'bottomleft' });
        leyenda.onAdd = () => {
            const div = L.DomUtil.create('div', 'carta-leyenda');
            const maresUsados = new Set((estado.datos.islas || []).map((i) => i.mar));
            div.innerHTML = `
                <strong>Convenciones</strong>
                ${mares.filter((m) => maresUsados.has(m.id)).map((m) => `
                    <span><i class="punto" style="background:${COLOR_MAR[m.id] || '#aaa'}"></i>${esc(m.nombre)}</span>`).join('')}
                <span><i class="barco-mini"></i>Tripulación</span>
                <span><i class="anillo"></i>Evento registrado</span>
                <span><i class="linea"></i>Ruta entre islas</span>
                <span><i class="linea linea--ruta"></i>Recorrido</span>`;
            L.DomEvent.disableClickPropagation(div);
            return div;
        };
        return leyenda;
    }

    // ----------------------------------------------------------------- datos

    async function cargar() {
        const r = await fetch(cfg.datos, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (!r.ok) throw new Error(`No se pudo cargar el mapa (HTTP ${r.status}).`);
        return r.json();
    }

    async function iniciar() {
        const aviso = $('#carta-estado');

        try {
            estado.datos = await cargar();
        } catch (e) {
            aviso.textContent = e.message;
            aviso.classList.add('error');
            return;
        }

        ANCHO = estado.datos.limites?.ancho || ANCHO;
        ALTO = estado.datos.limites?.alto || ALTO;

        estado.fondo = crearFondo(estado.datos.islas || []).addTo(mapa);
        mapa.setMaxBounds([[-ALTO * 0.25, -ANCHO * 0.25], [ALTO * 1.25, ANCHO * 1.25]]);

        dibujarTodo();

        L.control.layers(null, {
            'Islas': capas.islas,
            'Tripulaciones': capas.tripulaciones,
            'Otras ubicaciones': capas.otros,
            'Eventos': capas.eventos,
            'Rutas entre islas': capas.conexiones,
            'Recorridos (todas)': capas.recorridos,
        }, { collapsed: true, position: 'topright' }).addTo(mapa);

        ['conexiones', 'eventos', 'islas', 'otros', 'tripulaciones', 'seleccion', 'edicion'].forEach((c) => capas[c].addTo(mapa));
        crearLeyenda(estado.datos.mares || []).addTo(mapa);
        coordenadas.addTo(mapa);

        verTodo(false);
        aviso.hidden = true;

        if (cfg.puedeEditar) prepararFormularioUbicar();
    }

    function dibujarTodo() {
        Object.entries(capas).forEach(([nombre, capa]) => {
            if (nombre !== 'seleccion' && nombre !== 'edicion') capa.clearLayers();
        });
        estado.marcadoresIsla.clear();
        estado.marcadoresTripulacion.clear();

        dibujarConexiones();
        dibujarIslas();
        dibujarTripulaciones();
        dibujarOtros();
        dibujarRecorridos();
        llenarLista();
        llenarBuscador();
        actualizarEtiquetas();
    }

    // ----------------------------------------------------------------- islas

    function dibujarIslas() {
        const islas = estado.datos.islas || [];
        const principales = etiquetasPrincipales(islas);

        islas.forEach((isla) => {
            const color = COLOR_MAR[isla.mar] || '#b9c4c9';
            // La isla ya está dibujada en el fondo: aquí va un círculo casi
            // invisible para poder hacerle clic, con un punto del color de su mar.
            const marcador = L.circleMarker(aLatLng(isla.coords), {
                radius: 14,
                stroke: false,
                fillColor: '#ffffff',
                fillOpacity: 0.001,
                className: 'isla-zona',
            });
            L.circleMarker(aLatLng(isla.coords), {
                radius: 3.5,
                color: '#fff8e6',
                weight: 1.4,
                fillColor: color,
                fillOpacity: 1,
                interactive: false,
                className: 'isla-punto',
            }).addTo(capas.islas);

            marcador.bindTooltip(esc(isla.nombre), {
                permanent: true,
                direction: 'right',
                offset: [10, 0],
                className: 'etiqueta-isla' + (principales.has(isla.id) ? '' : ' etiqueta-isla--menor'),
            });
            marcador.bindPopup(() => popupIsla(isla), { className: 'popup-carta', maxWidth: 300 });
            marcador.on('click', () => mostrarDetalleIsla(isla));
            marcador.addTo(capas.islas);
            estado.marcadoresIsla.set(isla.id, marcador);

            if (isla.eventos?.length) {
                const nivel = nivelMaximo(isla.eventos);
                L.circleMarker(aLatLng(isla.coords), {
                    radius: 19,
                    color: COLOR_NIVEL[nivel] || '#ffd23f',
                    weight: 2,
                    opacity: 0.85,
                    dashArray: '5 4',
                    fill: false,
                    interactive: false,
                    className: 'anillo-evento',
                }).addTo(capas.eventos);
            }
        });
    }

    /**
     * Islas cuyo nombre se ve aunque el mapa esté lejos: se calcula la caja
     * que ocupa cada nombre a ese acercamiento y se descartan los que se
     * pisarían con uno más importante (más eventos y más rutas).
     */
    function etiquetasPrincipales(islas) {
        const unidadesPorPx = 1 / Math.pow(2, -0.5);
        const caja = (i) => {
            const ancho = (i.nombre.length * 7 + 14) * unidadesPorPx;
            const alto = 16 * unidadesPorPx;
            return [i.coords[0] - 6, i.coords[1] - alto / 2, i.coords[0] + ancho, i.coords[1] + alto / 2];
        };
        const choca = (a, b) => a[0] < b[2] && b[0] < a[2] && a[1] < b[3] && b[1] < a[3];
        const peso = (i) => (i.eventos?.length || 0) * 2 + conexionesDe(i.id).length + (tamanoDe(i) >= 17 ? 2 : 0);

        const elegidas = [];
        [...islas].sort((a, b) => peso(b) - peso(a)).forEach((i) => {
            const c = caja(i);
            if (!elegidas.some((e) => choca(e.caja, c))) elegidas.push({ id: i.id, caja: c });
        });
        return new Set(elegidas.map((e) => e.id));
    }

    function nivelMaximo(eventos) {
        const orden = ['baja', 'media', 'alta', 'crítica', 'critica', 'crítico'];
        return eventos
            .map((e) => String(e.nivel || '').toLowerCase())
            .sort((a, b) => orden.indexOf(b) - orden.indexOf(a))[0];
    }

    function conexionesDe(idIsla) {
        return (estado.datos.conexiones || []).filter((c) => c.origen === idIsla || c.destino === idIsla);
    }

    function islaPorId(id) {
        return (estado.datos.islas || []).find((i) => i.id === id);
    }

    function popupIsla(isla) {
        const presentes = (estado.datos.tripulaciones || []).filter((t) => t.posicion?.isla === isla.nombre);
        return `
            <h3>${esc(isla.nombre)}</h3>
            <p class="popup-meta">${esc(nombreMar(isla.mar))}${isla.tipo ? ' · ' + esc(isla.tipo) : ''}</p>
            ${isla.descripcion ? `<p>${esc(isla.descripcion)}</p>` : ''}
            ${isla.eventos?.length ? `<p><b>${isla.eventos.length}</b> evento(s) registrado(s)</p>` : ''}
            ${presentes.length ? `<p>⚓ ${presentes.map((t) => esc(t.nombre)).join(', ')}</p>` : ''}
            <p class="popup-coords">X ${isla.coords[0]} · Y ${isla.coords[1]}</p>`;
    }

    // ------------------------------------------------------------ conexiones

    /** Puntos de una curva suave entre dos coordenadas (para que las rutas parezcan travesías). */
    function curva(a, b, curvatura = 0.18, pasos = 24) {
        const mx = (a[0] + b[0]) / 2;
        const my = (a[1] + b[1]) / 2;
        const dx = b[0] - a[0];
        const dy = b[1] - a[1];
        const cx = mx - dy * curvatura;
        const cy = my + dx * curvatura;
        const puntos = [];
        for (let i = 0; i <= pasos; i++) {
            const t = i / pasos;
            const x = (1 - t) * (1 - t) * a[0] + 2 * (1 - t) * t * cx + t * t * b[0];
            const y = (1 - t) * (1 - t) * a[1] + 2 * (1 - t) * t * cy + t * t * b[1];
            puntos.push(aLatLng([x, y]));
        }
        return puntos;
    }

    function dibujarConexiones() {
        (estado.datos.conexiones || []).forEach((c) => {
            const a = islaPorId(c.origen);
            const b = islaPorId(c.destino);
            if (!a || !b) return;

            const puntos = curva(a.coords, b.coords, 0.12);
            L.polyline(puntos, { color: '#03121e', weight: 4, opacity: 0.25, interactive: false }).addTo(capas.conexiones);
            L.polyline(puntos, {
                color: '#f3e3b5',
                weight: 1.6,
                opacity: 0.6,
                dashArray: '1 7',
                lineCap: 'round',
                className: 'ruta-isla',
            })
                .bindTooltip(`${esc(a.nombre)} → ${esc(b.nombre)}` +
                    (c.tiempo != null ? ` · ${c.tiempo} día(s)` : '') +
                    (c.observaciones ? `<br><small>${esc(c.observaciones)}</small>` : ''), { sticky: true, className: 'tooltip-carta' })
                .addTo(capas.conexiones);
        });
    }

    // --------------------------------------------------------- tripulaciones

    const iconoBarco = (activa, seleccionada) => L.divIcon({
        className: 'icono-tripulacion' + (activa ? '' : ' icono-tripulacion--inactiva') + (seleccionada ? ' seleccionada' : ''),
        iconSize: [38, 38],
        iconAnchor: [19, 19],
        html: `<svg viewBox="0 0 40 40" aria-hidden="true">
                 <circle cx="20" cy="20" r="18" class="aro"/>
                 <circle cx="20" cy="20" r="15" class="fondo"/>
                 <path d="M20 8v17" class="mastil"/>
                 <path d="M20.6 9.5c4.5 2 6.8 6 7.4 11.2h-7.4z" class="vela"/>
                 <path d="M19.4 11.5c-3.4 1.8-5 5-5.4 9.2h5.4z" class="vela vela--2"/>
                 <path d="M20 8h6.5v3.6H20z" class="bandera"/>
                 <circle cx="23.2" cy="9.8" r="1.05" class="calavera"/>
                 <path d="M9.5 24.5h21l-3.4 5.2H13z" class="casco"/>
                 <path d="M8 31.5q3 -1.6 6 0t6 0t6 0t6 0" class="ola"/>
               </svg>`,
    });

    const activa = (t) => /activ|naveg/i.test(t.estado || '') || !t.estado;

    function dibujarTripulaciones() {
        // Si varias están en el mismo punto, se reparten un poco para que no se tapen.
        const ocupados = new Map();

        (estado.datos.tripulaciones || []).forEach((t) => {
            if (!t.posicion) return;

            const clave = t.posicion.coords.join(',');
            const n = ocupados.get(clave) || 0;
            ocupados.set(clave, n + 1);
            const angulo = n * 2.4;
            const desplazado = n === 0 ? t.posicion.coords : [
                t.posicion.coords[0] + Math.cos(angulo) * 16 * Math.ceil(n / 6),
                t.posicion.coords[1] + Math.sin(angulo) * 16 * Math.ceil(n / 6),
            ];

            const marcador = L.marker(aLatLng(desplazado), { icon: iconoBarco(activa(t)), riseOnHover: true, zIndexOffset: 500 })
                .bindTooltip(esc(t.nombre), { direction: 'bottom', offset: [0, 18], className: 'etiqueta-tripulacion' })
                .bindPopup(() => popupTripulacion(t), { className: 'popup-carta', maxWidth: 300 })
                .on('click', () => seleccionarTripulacion(t, false));

            marcador.addTo(capas.tripulaciones);
            estado.marcadoresTripulacion.set(t.id, marcador);
        });
    }

    function popupTripulacion(t) {
        const p = t.posicion;
        return `
            <h3>${esc(t.nombre)}</h3>
            <p class="popup-meta">${esc(t.estado || 'Sin estado')}${t.barco ? ' · ' + esc(t.barco.nombre) : ''}</p>
            <p>📍 ${p.isla ? esc(p.isla) : 'En altamar'} <small>(X ${p.coords[0]} · Y ${p.coords[1]})</small></p>
            <p class="popup-coords">${esc(p.fuente)} · ${fecha(p.fecha)}</p>`;
    }

    function dibujarOtros() {
        (estado.datos.otros || []).forEach((o) => {
            L.marker(aLatLng(o.posicion.coords), {
                icon: L.divIcon({
                    className: 'icono-otro icono-otro--' + o.tipo,
                    iconSize: [22, 22],
                    iconAnchor: [11, 11],
                    html: o.tipo === 'organizacion' ? '⚑' : '☠',
                }),
            })
                .bindTooltip(`${esc(o.nombre)}<br><small>${o.tipo === 'organizacion' ? 'Organización' : 'Personaje'} · ${o.posicion.isla ? esc(o.posicion.isla) : 'altamar'}</small>`,
                    { direction: 'top', className: 'tooltip-carta' })
                .addTo(capas.otros);
        });
    }

    function dibujarRecorridos() {
        (estado.datos.rutas || []).forEach((ruta, i) => {
            L.polyline(ruta.puntos.map((p) => aLatLng(p.coords)), {
                color: COLORES_RUTA[i % COLORES_RUTA.length],
                weight: 2.5,
                opacity: 0.75,
                dashArray: '8 6',
            })
                .bindTooltip(`Recorrido de ${esc(ruta.nombre)}`, { sticky: true, className: 'tooltip-carta' })
                .addTo(capas.recorridos);
        });
    }

    // ------------------------------------------------------- panel lateral

    $$('.carta__pestanas [data-pestana]').forEach((b) => b.addEventListener('click', () => abrirPestana(b.dataset.pestana)));

    function abrirPestana(nombre) {
        $$('.carta__pestanas [data-pestana]').forEach((b) => b.classList.toggle('activa', b.dataset.pestana === nombre));
        $$('.carta__seccion').forEach((s) => s.classList.toggle('activa', s.dataset.seccion === nombre));
    }

    function llenarLista() {
        const lista = $('#lista-tripulaciones');
        const tripulaciones = [...(estado.datos.tripulaciones || [])]
            .sort((a, b) => (!!b.posicion - !!a.posicion) || a.nombre.localeCompare(b.nombre));

        if (!tripulaciones.length) {
            lista.innerHTML = '<li class="vacio">No hay tripulaciones registradas.</li>';
            return;
        }

        lista.innerHTML = tripulaciones.map((t) => `
            <li>
                <button type="button" data-tripulacion="${t.id}" class="${t.posicion ? '' : 'sin-ubicacion'}">
                    <span class="lista-tripulaciones__nombre">${esc(t.nombre)}</span>
                    <span class="estado ${activa(t) ? 'estado--activa' : ''}">${esc(t.estado || '—')}</span>
                    <small>${t.posicion ? '📍 ' + esc(t.posicion.isla || 'En altamar') : 'Sin ubicación registrada'}</small>
                </button>
            </li>`).join('');

        $$('[data-tripulacion]', lista).forEach((b) => b.addEventListener('click', () => {
            const t = estado.datos.tripulaciones.find((x) => x.id === Number(b.dataset.tripulacion));
            seleccionarTripulacion(t, true);
        }));
    }

    function mostrarDetalleIsla(isla) {
        const conexiones = conexionesDe(isla.id);
        const presentes = (estado.datos.tripulaciones || []).filter((t) => t.posicion?.isla === isla.nombre);

        $('#carta-detalle').innerHTML = `
            <h3>${esc(isla.nombre)}</h3>
            <p class="popup-meta">${esc(nombreMar(isla.mar))}${isla.tipo ? ' · ' + esc(isla.tipo) : ''} · X ${isla.coords[0]}, Y ${isla.coords[1]}</p>
            ${isla.descripcion ? `<p>${esc(isla.descripcion)}</p>` : ''}

            <h4>Tripulaciones en la isla</h4>
            ${presentes.length ? `<ul class="lista-simple">${presentes.map((t) => `<li>⚓ ${esc(t.nombre)}</li>`).join('')}</ul>` : '<p class="vacio">Ninguna registrada.</p>'}

            <h4>Rutas desde y hacia la isla</h4>
            ${conexiones.length ? `<ul class="lista-simple">${conexiones.map((c) => {
                const otra = islaPorId(c.origen === isla.id ? c.destino : c.origen);
                const sentido = c.origen === isla.id ? '→' : '←';
                return `<li>${sentido} ${esc(otra?.nombre || '¿?')}${c.tiempo != null ? ` · ${c.tiempo} día(s)` : ''}</li>`;
            }).join('')}</ul>` : '<p class="vacio">Sin rutas registradas.</p>'}

            <h4>Eventos</h4>
            ${isla.eventos?.length ? `<ul class="lista-eventos">${isla.eventos.map((e) => `
                <li>
                    <span class="nivel" style="--nivel:${COLOR_NIVEL[String(e.nivel || '').toLowerCase()] || '#999'}">${esc(e.nivel || '—')}</span>
                    <b>${esc(e.nombre)}</b>${e.tipo ? ` <small>(${esc(e.tipo)})</small>` : ''}
                    ${e.descripcion ? `<p>${esc(e.descripcion)}</p>` : ''}
                </li>`).join('')}</ul>` : '<p class="vacio">Sin eventos registrados.</p>'}`;

        abrirPestana('detalle');
    }

    function seleccionarTripulacion(t, volar) {
        capas.seleccion.clearLayers();

        estado.marcadoresTripulacion.forEach((m, id) => m.setIcon(iconoBarco(activa(estado.datos.tripulaciones.find((x) => x.id === id)), id === t.id)));

        if (volar && t.posicion) {
            mapa.flyTo(aLatLng(t.posicion.coords), Math.max(mapa.getZoom(), 1), { duration: 0.6 });
            estado.marcadoresTripulacion.get(t.id)?.openPopup();
        }

        const p = t.posicion;
        $('#carta-detalle').innerHTML = `
            <h3>${esc(t.nombre)}</h3>
            <p class="popup-meta">${esc(t.estado || 'Sin estado')}${t.barco ? ' · Barco: ' + esc(t.barco.nombre) : ''}</p>
            ${t.bounty != null ? `<p>Recompensa general: <b>${berries(t.bounty)}</b></p>` : ''}
            ${p ? `
                <p>📍 <b>${p.isla ? esc(p.isla) : 'En altamar'}</b> <small>(X ${p.coords[0]} · Y ${p.coords[1]})</small><br>
                <small>${esc(p.fuente)} · ${fecha(p.fecha)}</small></p>` : '<p class="vacio">Sin ubicación registrada.</p>'}

            <div class="acciones-detalle">
                <button type="button" class="boton-carta" id="btn-recorrido">🧭 Ver recorrido</button>
                ${cfg.puedePredecir ? '<button type="button" class="boton-carta boton-carta--oro" id="btn-predecir">🔮 Predecir próximo movimiento</button>' : ''}
                <button type="button" class="boton-carta" id="btn-historial-pred">📜 Predicciones anteriores</button>
            </div>
            <div id="detalle-resultado"></div>`;

        $('#btn-recorrido').addEventListener('click', () => verRecorrido(t));
        $('#btn-predecir')?.addEventListener('click', () => predecir(t));
        $('#btn-historial-pred').addEventListener('click', () => verPredicciones(t));

        if (cfg.puedeEditar) {
            $('#ubicar-tipo').value = 'tripulacion';
            llenarEntidades();
            $('#ubicar-id').value = String(t.id);
        }

        abrirPestana('detalle');
    }

    // ------------------------------------------------- recorrido y predicción

    async function pedirJSON(url, opciones = {}) {
        const r = await fetch(url, {
            credentials: 'same-origin',
            ...opciones,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(opciones.body ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf } : {}),
                ...(opciones.headers || {}),
            },
        });
        const cuerpo = await r.json().catch(() => ({}));
        if (!r.ok) {
            const errores = cuerpo.errors ? Object.values(cuerpo.errors).flat() : [];
            const mensaje = errores[0] || cuerpo.message || `Error HTTP ${r.status}`;
            throw new Error(r.status === 403 ? 'No tienes permiso para esta acción.' :
                r.status === 419 ? 'La sesión venció. Recarga la página.' : mensaje);
        }
        return cuerpo;
    }

    async function verRecorrido(t) {
        const caja = $('#detalle-resultado');
        caja.innerHTML = '<p class="vacio">Consultando la bitácora de navegación…</p>';

        try {
            const r = await pedirJSON(`${cfg.recorrido}/tripulacion/${t.id}`);
            capas.seleccion.clearLayers();

            if (!r.data.length) {
                caja.innerHTML = '<p class="vacio">Esta tripulación no tiene movimientos registrados.</p>';
                return;
            }

            const puntos = r.data.map((p) => aLatLng(p.coords));
            L.polyline(puntos, { color: '#ffd166', weight: 4, opacity: 0.9, className: 'ruta-animada' }).addTo(capas.seleccion);
            r.data.forEach((p, i) => {
                L.marker(aLatLng(p.coords), {
                    icon: L.divIcon({ className: 'parada', html: String(i + 1), iconSize: [20, 20], iconAnchor: [10, 10] }),
                    zIndexOffset: 800,
                })
                    .bindTooltip(`<b>${i + 1}. ${esc(p.isla || 'Altamar')}</b> · ${fecha(p.fecha)}${p.nota ? `<br>${esc(p.nota)}` : ''}`, { className: 'tooltip-carta' })
                    .addTo(capas.seleccion);
            });
            mapa.flyToBounds(L.latLngBounds(puntos).pad(0.2), { duration: 0.6 });

            caja.innerHTML = `
                <h4>Recorrido (${r.total} paradas${r.fuente !== 'tripulacion' ? ', según su barco' : ''})</h4>
                <ol class="lista-recorrido">${r.data.map((p) => `
                    <li><b>${esc(p.isla || 'Altamar')}</b> <small>${fecha(p.fecha)}</small>${p.nota ? `<p>${esc(p.nota)}</p>` : ''}</li>`).join('')}
                </ol>`;
        } catch (e) {
            caja.innerHTML = `<p class="error">${esc(e.message)}</p>`;
        }
    }

    async function predecir(t) {
        const caja = $('#detalle-resultado');
        caja.innerHTML = '<p class="vacio">El motor de reglas está evaluando las rutas…</p>';

        try {
            const r = await pedirJSON(cfg.predecir, { method: 'POST', body: JSON.stringify({ tripulacion: t.id }) });
            const d = r.data;
            capas.seleccion.clearLayers();

            const origen = islaPorId(d.origen.id);
            d.candidatas.forEach((c, i) => {
                L.polyline([aLatLng(origen.coords), aLatLng(c.isla.coords)], {
                    color: i === 0 ? '#c77dff' : '#c77dff',
                    weight: i === 0 ? 4 : 2,
                    opacity: i === 0 ? 0.95 : 0.4,
                    dashArray: i === 0 ? '10 6' : '3 6',
                    className: i === 0 ? 'ruta-animada' : '',
                }).bindTooltip(`${esc(c.isla.nombre)}: ${c.puntaje} pts`, { sticky: true, className: 'tooltip-carta' })
                    .addTo(capas.seleccion);
            });
            L.circleMarker(aLatLng(d.destino.coords || islaPorId(d.destino.id).coords), {
                radius: 16, color: '#c77dff', weight: 3, fill: false, className: 'pulso',
            }).addTo(capas.seleccion);
            mapa.flyToBounds(L.latLngBounds([aLatLng(origen.coords), ...d.candidatas.map((c) => aLatLng(c.isla.coords))]).pad(0.4), { duration: 0.6 });

            caja.innerHTML = `
                <div class="prediccion">
                    <p class="prediccion__titulo">Destino más probable</p>
                    <p class="prediccion__destino">${esc(d.origen.nombre)} → <b>${esc(d.destino.nombre)}</b></p>
                    <p class="prediccion__puntaje">${d.puntaje} puntos · guardada${d.id ? ' (#' + d.id + ')' : ''}</p>
                </div>
                <h4>Islas evaluadas</h4>
                ${tablaCandidatas(d.candidatas)}`;
        } catch (e) {
            caja.innerHTML = `<p class="error">${esc(e.message)}</p>`;
        }
    }

    function tablaCandidatas(candidatas) {
        return `<ol class="lista-candidatas">${candidatas.map((c, i) => `
            <li class="${i === 0 ? 'ganadora' : ''}">
                <div class="lista-candidatas__cabeza"><b>${esc(c.isla.nombre)}</b><span>${c.puntaje} pts</span></div>
                <ul>${c.factores.map((f) => `
                    <li><span class="${f.puntos >= 0 ? 'mas' : 'menos'}">${f.puntos >= 0 ? '+' : ''}${f.puntos}</span>
                        ${esc(f.regla.replace(/_/g, ' '))} <small>${esc(f.detalle)}</small></li>`).join('')}
                </ul>
            </li>`).join('')}</ol>`;
    }

    async function verPredicciones(t) {
        const caja = $('#detalle-resultado');
        caja.innerHTML = '<p class="vacio">Buscando predicciones…</p>';

        try {
            const r = await pedirJSON(`${cfg.predicciones}?tripulacion=${t.id}`);
            caja.innerHTML = r.data.length ? `
                <h4>Predicciones anteriores</h4>
                <ul class="lista-simple">${r.data.map((p) => `
                    <li>${fecha(p.fecha)} · ${p.origen ? esc(p.origen.nombre) + ' → ' : ''}<b>${esc(p.destino?.nombre || 'sin destino')}</b>
                        ${p.puntaje != null ? `<small>(${p.puntaje} pts)</small>` : ''}
                        ${p.acierto === true ? ' ✅' : p.acierto === false ? ' ❌' : ''}
                        ${typeof p.factores === 'string' ? `<p>${esc(p.factores)}</p>` : ''}
                    </li>`).join('')}
                </ul>` : '<p class="vacio">No hay predicciones guardadas para esta tripulación.</p>';
        } catch (e) {
            caja.innerHTML = `<p class="error">${esc(e.message)}</p>`;
        }
    }

    // ----------------------------------------------------------- buscador

    function llenarBuscador() {
        const opciones = [
            ...(estado.datos.islas || []).map((i) => `<option value="${esc(i.nombre)}">Isla</option>`),
            ...(estado.datos.tripulaciones || []).map((t) => `<option value="${esc(t.nombre)}">Tripulación</option>`),
        ];
        $('#carta-sugerencias').innerHTML = opciones.join('');
    }

    $('#carta-buscar').addEventListener('change', (e) => buscar(e.target.value));
    $('#carta-buscar').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            buscar(e.target.value);
        }
    });

    function buscar(texto) {
        const q = texto.trim().toLowerCase();
        if (!q || !estado.datos) return;

        const t = estado.datos.tripulaciones.find((x) => x.nombre.toLowerCase() === q) ||
            estado.datos.tripulaciones.find((x) => x.nombre.toLowerCase().includes(q));
        const i = estado.datos.islas.find((x) => x.nombre.toLowerCase() === q) ||
            estado.datos.islas.find((x) => x.nombre.toLowerCase().includes(q));

        if (t && (!i || t.nombre.toLowerCase() === q)) {
            seleccionarTripulacion(t, true);
            if (!t.posicion) $('#detalle-resultado').innerHTML = '<p class="vacio">Esta tripulación todavía no tiene ubicación en el mapa.</p>';
        } else if (i) {
            mapa.flyTo(aLatLng(i.coords), Math.max(mapa.getZoom(), 1.5), { duration: 0.6 });
            estado.marcadoresIsla.get(i.id)?.openPopup();
            mostrarDetalleIsla(i);
        }
    }

    // ------------------------------------------------- ver todo / expandir

    function verTodo(animar = true) {
        // La carta completa, con su marco, como una carta náutica sobre la mesa.
        const limites = L.latLngBounds([[0, 0], [ALTO, ANCHO]]);
        animar ? mapa.flyToBounds(limites, { duration: 0.6 }) : mapa.fitBounds(limites);
    }

    $('#carta-centrar').addEventListener('click', () => verTodo(true));

    const botonExpandir = $('#carta-expandir');
    botonExpandir.addEventListener('click', () => {
        const expandida = carta.classList.contains('carta--expandida');

        if (!expandida) {
            if (carta.requestFullscreen) {
                carta.requestFullscreen().catch(() => ponerExpandida(true));
            } else {
                ponerExpandida(true);
            }
        } else if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            ponerExpandida(false);
        }
    });

    document.addEventListener('fullscreenchange', () => ponerExpandida(document.fullscreenElement === carta));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && carta.classList.contains('carta--expandida') && !document.fullscreenElement) {
            ponerExpandida(false);
        }
    });

    function ponerExpandida(si) {
        carta.classList.toggle('carta--expandida', si);
        document.body.classList.toggle('carta-abierta', si);
        botonExpandir.setAttribute('aria-pressed', si ? 'true' : 'false');
        botonExpandir.querySelector('span').textContent = si ? 'Reducir' : 'Expandir';
        setTimeout(() => {
            mapa.invalidateSize();
            verTodo(false);
        }, 150);
    }

    // ---------------------------------------- nombres según el acercamiento

    function actualizarEtiquetas() {
        const z = mapa.getZoom();
        const contenedor = mapa.getContainer();
        contenedor.classList.toggle('zoom-lejos', z < 0.5);
        contenedor.classList.toggle('zoom-muy-lejos', z < -0.75);
    }
    mapa.on('zoomend', actualizarEtiquetas);

    // ------------------------------------------------------ ubicar entidades

    function prepararFormularioUbicar() {
        const form = $('#form-ubicar');
        const tipo = $('#ubicar-tipo');
        const isla = $('#ubicar-isla');
        const x = $('#ubicar-x');
        const y = $('#ubicar-y');
        const elegir = $('#ubicar-elegir');
        const mensaje = $('#ubicar-mensaje');

        isla.innerHTML = '<option value="">— En altamar (clic en el mapa) —</option>' +
            [...estado.datos.islas].sort((a, b) => a.nombre.localeCompare(b.nombre))
                .map((i) => `<option value="${i.id}">${esc(i.nombre)}</option>`).join('');

        llenarEntidades();
        tipo.addEventListener('change', llenarEntidades);

        isla.addEventListener('change', () => {
            const i = islaPorId(Number(isla.value));
            if (i) {
                x.value = i.coords[0];
                y.value = i.coords[1];
                marcarPunto(i.coords);
                mapa.flyTo(aLatLng(i.coords), Math.max(mapa.getZoom(), 1), { duration: 0.5 });
            }
        });

        [x, y].forEach((campo) => campo.addEventListener('input', () => {
            isla.value = '';
            if (x.value !== '' && y.value !== '') marcarPunto([Number(x.value), Number(y.value)]);
        }));

        elegir.addEventListener('click', () => modoElegir(!estado.eligiendoPunto));

        mapa.on('click', (e) => {
            if (!estado.eligiendoPunto) return;
            const [px, py] = aXY(e.latlng);
            if (px < 0 || px > ANCHO || py < 0 || py > ALTO) {
                mensaje.className = 'mensaje error';
                mensaje.textContent = 'Ese punto está fuera del mapa.';
                return;
            }
            x.value = px;
            y.value = py;
            // Si el clic cae sobre una isla, se toma esa isla.
            const cercana = estado.datos.islas.find((i) => Math.hypot(i.coords[0] - px, i.coords[1] - py) <= 12);
            isla.value = cercana ? String(cercana.id) : '';
            if (cercana) {
                x.value = cercana.coords[0];
                y.value = cercana.coords[1];
            }
            marcarPunto([Number(x.value), Number(y.value)]);
            modoElegir(false);
            abrirPestana('ubicar');
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const id = $('#ubicar-id').value;

            if (!id || x.value === '' || y.value === '') {
                mensaje.className = 'mensaje error';
                mensaje.textContent = 'Elige la entidad y el punto (una isla o un clic en el mapa).';
                return;
            }

            mensaje.className = 'mensaje';
            mensaje.textContent = 'Guardando…';

            try {
                const r = await pedirJSON(`${cfg.ubicar}/${encodeURIComponent(tipo.value)}/${encodeURIComponent(id)}`, {
                    method: 'PUT',
                    body: JSON.stringify({ x: Number(x.value), y: Number(y.value), isla: isla.value ? Number(isla.value) : null }),
                });
                mensaje.className = 'mensaje ok';
                mensaje.textContent = `Ubicación guardada: ${r.datos?.nombre || ''} quedó en ${isla.value ? $('option:checked', isla).textContent : 'X ' + x.value + ', Y ' + y.value}.`;

                estado.datos = await cargar();
                dibujarTodo();
                capas.edicion.clearLayers();
            } catch (err) {
                mensaje.className = 'mensaje error';
                mensaje.textContent = err.message;
            }
        });
    }

    function llenarEntidades() {
        const tipo = $('#ubicar-tipo');
        const select = $('#ubicar-id');
        if (!tipo || !select) return;

        const lista = estado.datos?.entidades?.[tipo.value] || [];
        select.innerHTML = lista.length
            ? lista.map((e) => `<option value="${e.id}">${esc(e.nombre)}</option>`).join('')
            : '<option value="">(no hay registros)</option>';
    }

    function marcarPunto(coords) {
        capas.edicion.clearLayers();
        L.circleMarker(aLatLng(coords), { radius: 11, color: '#ffd166', weight: 3, fillOpacity: 0.25, className: 'pulso' })
            .addTo(capas.edicion);
    }

    function modoElegir(si) {
        estado.eligiendoPunto = si;
        mapa.getContainer().classList.toggle('eligiendo', si);
        const b = $('#ubicar-elegir');
        b.setAttribute('aria-pressed', si ? 'true' : 'false');
        b.textContent = si ? '✕ Cancelar: haz clic en el mapa' : '⌖ Elegir el punto en el mapa';
    }

    iniciar();
})();
