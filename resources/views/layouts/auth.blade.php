{{--
    Marco compartido de login, registro, pendiente, inicio y acceso denegado.
    Cada pantalla define:
      @section('titulo')  -> texto de la pestaña del navegador
      @section('tema')    -> tema-marina | tema-cp | tema-gobierno (opcional)
      @section('contenido')
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Acceso al sistema') · Red de Gobierno</title>
    <link rel="stylesheet" href="{{ asset('css/tema.css') }}">
</head>
<body class="@yield('tema', 'tema-marina')">

    <header class="encabezado">
        {{-- Emblema propio (un ancla dibujada con SVG), no es un logo oficial. --}}
        <svg class="emblema" viewBox="0 0 64 64" fill="none" stroke="currentColor"
             stroke-width="4" stroke-linecap="round" stroke-linejoin="round"
             role="img" aria-label="Emblema de ancla">
            <circle cx="32" cy="10" r="5"/>
            <path d="M32 15v40M20 26h24"/>
            <path d="M10 38c2 12 12 18 22 18s20-6 22-18"/>
            <path d="M10 38l-5 6M54 38l5 6"/>
        </svg>
        <h1>Red de Gobierno Mundial</h1>
        <p class="lema">Marina · Cipher Pol · Gobierno Afiliado</p>
    </header>

    <main class="expediente">
        @yield('contenido')
    </main>

    <p class="pie">Acceso restringido a personal autorizado</p>

    @stack('scripts')
</body>
</html>
