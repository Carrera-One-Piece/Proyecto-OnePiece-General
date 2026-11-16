<nav class="pestanas" aria-label="Secciones de inteligencia">
    <a
        href="{{ route('inteligencia.reportes.index') }}"
        @class([
            'activo' => request()->routeIs('inteligencia.reportes.*')
        ])
    >
        Reportes recibidos
    </a>

    <a
        href="{{ route('inteligencia.informes.index') }}"
        @class([
            'activo' => request()->routeIs('inteligencia.informes.*')
        ])
    >
        Informes consolidados
    </a>

    <a
        href="{{ route('inteligencia.estrategico.index') }}"
        @class([
            'activo' => request()->routeIs('inteligencia.estrategico.*')
        ])
    >
        Reporte estratégico
    </a>
</nav>