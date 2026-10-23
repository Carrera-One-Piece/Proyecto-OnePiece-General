{{--
    Tablón de recompensas (rebanada A): todos los piratas, de la recompensa
    más alta a la más baja, con buscador y filtros. Solo lectura.
--}}
@extends('layouts.panel')

@use('App\Models\Recompensa')
@use('App\Support\ImagenesRecompensas')

@section('titulo', 'Tablón de recompensas')

@section('contenido')
    <div class="encabezado-seccion">
        <div>
            <h2>Tablón de recompensas</h2>
            <p class="subtitulo">Piratas buscados por el Gobierno Mundial, de la recompensa más alta a la más baja.</p>
        </div>
        @can('RECOMPENSAS.CREAR')
            <a class="boton boton-chico" href="{{ route('recompensas.crear') }}">+ Emitir recompensa</a>
        @endcan
    </div>

    <form class="buscador" method="get" action="{{ route('recompensas.index') }}" role="search">
        <div class="campo-texto">
            <label for="q">Nombre o alias</label>
            <input type="search" id="q" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Ej.: Luffy, Barbablanca">
        </div>
        <div>
            <label for="tripulacion">Tripulación</label>
            <select id="tripulacion" name="tripulacion">
                <option value="">Todas</option>
                @foreach($tripulaciones as $t)
                    <option value="{{ $t->ID_tripulacion }}" @selected(($filtros['tripulacion'] ?? null) == $t->ID_tripulacion)>{{ $t->Nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                @foreach($estados as $estado)
                    <option value="{{ $estado }}" @selected(($filtros['estado'] ?? null) === $estado)>{{ $estado }}</option>
                @endforeach
            </select>
        </div>
        <div class="botones">
            <button type="submit" class="boton-chico">Buscar</button>
            <a class="boton boton-chico boton-claro" href="{{ route('recompensas.index') }}">Limpiar</a>
        </div>
    </form>

    <p class="resultados">
        @if($piratas->total() === 0)
            No se encontró ningún pirata con esos datos.
        @else
            Mostrando {{ $piratas->firstItem() }}–{{ $piratas->lastItem() }} de {{ $piratas->total() }} piratas.
        @endif
    </p>

    <ul class="tablon">
        @foreach($piratas as $pirata)
            {{-- La Activa si tiene; si no, la última que tuvo (para el filtro de canceladas). --}}
            @php($recompensa = $pirata->recompensas->firstWhere('Estado', Recompensa::ESTADO_ACTIVA)
                ?? $pirata->recompensas->sortByDesc('ID_recompensa')->first())
            <li>
                <a class="cartel-mini" href="{{ route('recompensas.pirata', $pirata->ID_personaje) }}">
                    <span class="cartel-mini__titulo">SE BUSCA</span>
                    <img src="{{ ImagenesRecompensas::cartel($pirata) }}" alt="" loading="lazy"
                         onerror="this.onerror=null;this.src='{{ asset(ImagenesRecompensas::POR_DEFECTO) }}'">
                    <span class="cartel-mini__nombre">{{ $pirata->Nombre }}</span>
                    @if($pirata->Alias)
                        <span class="cartel-mini__alias">«{{ $pirata->Alias }}»</span>
                    @endif
                    <span class="cartel-mini__tripulacion">{{ $pirata->tripulaciones->first()?->Nombre ?? 'Sin tripulación' }}</span>
                    <span class="cartel-mini__valor">
                        @if($recompensa)
                            {{ Recompensa::berries($recompensa->Valor) }}
                        @else
                            Sin recompensa
                        @endif
                    </span>
                    @if($recompensa && ! $recompensa->estaActiva())
                        <span><span class="estado {{ $recompensa->claseEstado() }}">{{ $recompensa->Estado }}</span></span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>

    {{ $piratas->links('pagination::default') }}
@endsection
