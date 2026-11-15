<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('titulo', 'Acceso al sistema') · Red de Gobierno</title>

    <link rel="stylesheet" href="{{ asset('css/tema.css') }}">
    <link rel="stylesheet" href="{{ asset('css/acceso-naval.css') }}">
</head>

<body class="@yield('tema', 'tema-marina') @yield('interfaz')">

    <div class="acceso-shell">
        <header class="encabezado">
            <svg
                class="emblema"
                viewBox="0 0 64 64"
                fill="none"
                stroke="currentColor"
                stroke-width="4"
                stroke-linecap="round"
                stroke-linejoin="round"
                role="img"
                aria-label="Emblema de ancla"
            >
                <circle cx="32" cy="10" r="5"/>
                <path d="M32 15v40M20 26h24"/>
                <path d="M10 38c2 12 12 18 22 18s20-6 22-18"/>
                <path d="M10 38l-5 6M54 38l5 6"/>
            </svg>

            <h1>Red de Gobierno Mundial</h1>

            <p class="lema">
                Marina · Cipher Pol · Gobierno Afiliado
            </p>

            @hasSection('interfaz')
                <p class="identificador-sistema">
                    Sistema de identificación y acceso
                </p>
            @endif
        </header>

        <main class="expediente">
            @yield('contenido')
        </main>
    </div>

    <p class="pie">
        Acceso restringido a personal autorizado
        · Red de Gobierno Mundial
    </p>

    @stack('scripts')
</body>
</html>