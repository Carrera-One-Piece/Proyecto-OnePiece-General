{{--
    Administrar usuarios (RF27). Lista con filtros por nombre/correo, estado y rol.
    Los botones de aprobar, rechazar y suspender solo salen con USUARIOS.EDITAR
    (el Almirante, por ejemplo, solo mira). Las rutas tienen su propio guardia.
--}}
@extends('layouts.panel')

@section('titulo', 'Administrar usuarios')

@section('contenido')
    <div class="encabezado-seccion">
        <div>
            <h2>Administrar usuarios</h2>
            <p class="subtitulo">Personal con acceso al sistema. Rechazar o suspender nunca borra la cuenta.</p>
        </div>
        <a class="boton boton-chico" href="{{ route('usuarios.pendientes') }}">
            Pendientes
            <span @class(['contador', 'contador--cero' => $totalPendientes === 0])>{{ $totalPendientes }}</span>
        </a>
    </div>

    <form class="buscador" method="get" action="{{ route('usuarios.index') }}" role="search">
        <div class="campo-texto">
            <label for="q">Nombre o correo</label>
            <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Ej.: Tashigi, @cipherpol.gov">
        </div>
        <div>
            <label for="estado">Estado</label>
            <select id="estado" name="estado">
                <option value="">Todos</option>
                @foreach(['Pendiente', 'Activo', 'Suspendido', 'Inactivo'] as $estado)
                    <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ $estado }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="rol">Rol</label>
            <select id="rol" name="rol">
                <option value="">Todos</option>
                @foreach($roles as $rol)
                    <option value="{{ $rol->ID_rol }}" @selected(request('rol') == $rol->ID_rol)>{{ $rol->Nombre_rol }}</option>
                @endforeach
            </select>
        </div>
        <div class="botones">
            <button type="submit" class="boton-chico">Filtrar</button>
            <a class="boton boton-chico boton-claro" href="{{ route('usuarios.index') }}">Limpiar</a>
        </div>
    </form>

    <p class="resultados">
        @if($usuarios->total() === 0)
            No hay usuarios con esos filtros.
        @else
            Mostrando {{ $usuarios->firstItem() }}–{{ $usuarios->lastItem() }} de {{ $usuarios->total() }} usuarios.
        @endif
    </p>

    @if($usuarios->total() > 0)
        <table class="tabla">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Organización</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    @can('USUARIOS.EDITAR')
                        <th>Acciones</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @foreach($usuarios as $usuario)
                    @php($esUsted = (int) $usuario->ID_usuario === (int) auth()->id())
                    <tr>
                        <td class="persona">
                            <strong>
                                {{ $usuario->Nombre }}
                                @if($esUsted)<span class="usted">(usted)</span>@endif
                            </strong>
                            <small>{{ $usuario->Correo }}</small>
                        </td>
                        <td>{{ $usuario->Nombre_organizacion ?? 'Sin organización' }}</td>
                        <td>{{ $usuario->Nombre_rol ?? 'Sin rol' }}</td>
                        <td><span class="estado estado--{{ strtolower($usuario->Estado) }}">{{ $usuario->Estado }}</span></td>
                        @can('USUARIOS.EDITAR')
                            <td>
                                <div class="fila-acciones">
                                    @if($usuario->Estado === 'Pendiente')
                                        <a class="boton boton-chico" href="{{ route('usuarios.aprobar.form', $usuario->ID_usuario) }}">Revisar</a>
                                        <form method="post" action="{{ route('usuarios.rechazar', $usuario->ID_usuario) }}"
                                              onsubmit="return confirm('¿Rechazar la solicitud de {{ addslashes($usuario->Nombre) }}?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="boton-chico boton-claro">Rechazar</button>
                                        </form>
                                    @elseif($usuario->Estado === 'Activo' && ! $esUsted)
                                        <form method="post" action="{{ route('usuarios.suspender', $usuario->ID_usuario) }}"
                                              onsubmit="return confirm('¿Suspender a {{ addslashes($usuario->Nombre) }}? No podrá entrar al sistema.');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="boton-chico boton-peligro">Suspender</button>
                                        </form>
                                    @else
                                        <span class="vacio">—</span>
                                    @endif
                                </div>
                            </td>
                        @endcan
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{ $usuarios->links('pagination::default') }}
@endsection
