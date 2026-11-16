{{--
    Bitácora de accesos (RNF08). Solo lectura: nadie edita ni borra líneas.
    Los textos y colores de cada acción están en Bitacora::ETIQUETAS.
--}}
@extends('layouts.panel')

@section('titulo', 'Bitácora de accesos')

@section('contenido')
    <h2>Bitácora de accesos</h2>
    <p class="subtitulo">Quién entró, quién intentó entrar y qué cambió cada uno. Lo más reciente primero.</p>

    <form class="buscador buscador--5" method="get" action="{{ route('bitacora.index') }}" role="search">
        <div class="campo-texto">
            <label for="correo">Correo</label>
            <input type="search" id="correo" name="correo" value="{{ request('correo') }}" placeholder="Ej.: gestor@marina.gov">
        </div>
        <div>
            <label for="accion">Acción</label>
            <select id="accion" name="accion">
                <option value="">Todas</option>
                @foreach($acciones as $codigo => $texto)
                    <option value="{{ $codigo }}" @selected(request('accion') === $codigo)>{{ $texto }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="desde">Desde</label>
            <input type="date" id="desde" name="desde" value="{{ request('desde') }}">
        </div>
        <div>
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="{{ request('hasta') }}">
        </div>
        <div class="botones">
            <button type="submit" class="boton-chico">Filtrar</button>
            <a class="boton boton-chico boton-claro" href="{{ route('bitacora.index') }}">Limpiar</a>
        </div>
    </form>

    <p class="resultados">
        @if($registros->total() === 0)
            No hay registros con esos filtros.
        @else
            Mostrando {{ $registros->firstItem() }}–{{ $registros->lastItem() }} de {{ $registros->total() }} registros.
        @endif
    </p>

    @if($registros->total() > 0)
        <table class="tabla bitacora">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Acción</th>
                    <th>Quién</th>
                    <th>Detalle</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @foreach($registros as $registro)
                    <tr>
                        <td class="fecha">{{ $registro->Fecha?->format('d/m/Y H:i:s') }}</td>
                        <td><span class="estado estado--{{ $registro->color() }}" title="{{ $registro->Accion }}">{{ $registro->etiqueta() }}</span></td>
                        {{--
                            El nombre solo se muestra si el usuario de hoy tiene el mismo correo
                            que quedó anotado. Al reimportar el .sql los usuarios se crean de nuevo
                            y un número viejo puede quedar apuntando a otra persona.
                        --}}
                        @php($mismoUsuario = $registro->usuario
                            && (! $registro->Correo_intento || $registro->usuario->Correo === $registro->Correo_intento))
                        <td class="persona">
                            @if($mismoUsuario)
                                <strong>{{ $registro->usuario->Nombre }}</strong>
                                <small>{{ $registro->usuario->Correo }}</small>
                            @else
                                {{-- Intentos fallidos con un correo que no existe, por ejemplo. --}}
                                <small>{{ $registro->Correo_intento ?? '—' }}</small>
                            @endif
                        </td>
                        <td class="detalle">{{ $registro->Detalle ?? '' }}</td>
                        <td class="ip">{{ $registro->IP ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{ $registros->links('pagination::default') }}
@endsection
