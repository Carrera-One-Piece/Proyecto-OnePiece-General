{{--
    Dos pantallas en una (así la dejó la rebanada C del login):
      - $usuario lleno -> formulario para aprobar a esa persona y darle un rol.
      - $usuario null  -> lista de todos los pendientes.
    Nadie elige su propio rol: lo asigna el Experto en Seguridad aquí.
--}}
@extends('layouts.panel')

@section('titulo', $usuario ? 'Aprobar usuario' : 'Usuarios pendientes')

@section('contenido')
    @if($usuario)
        <p><a href="{{ route('usuarios.pendientes') }}">← Volver a pendientes</a></p>

        <h2>Aprobar usuario</h2>
        <p class="subtitulo">Revise los datos y asígnele el rol con el que va a trabajar.</p>

        <div class="ficha-usuario">
            <div class="panel-blanco">
                <h3>Solicitud</h3>
                <dl class="datos">
                    <dt>Nombre</dt>
                    <dd>{{ $usuario->Nombre }}</dd>

                    <dt>Correo</dt>
                    <dd>{{ $usuario->Correo }}</dd>

                    <dt>Organización</dt>
                    <dd>{{ $usuario->Nombre_organizacion ?? 'Sin organización' }}</dd>

                    <dt>Estado</dt>
                    <dd><span class="estado estado--{{ strtolower($usuario->Estado) }}">{{ $usuario->Estado }}</span></dd>
                </dl>
                <p class="ayuda">La organización es informativa: lo que puede hacer en el sistema lo decide el rol.</p>
            </div>

            <div class="panel-blanco">
                <h3>Asignar rol</h3>
                <form method="post" action="{{ route('usuarios.aprobar', $usuario->ID_usuario) }}">
                    @csrf
                    <label for="ID_rol">Rol</label>
                    <select id="ID_rol" name="ID_rol" required>
                        <option value="">— Escoja un rol —</option>
                        @foreach($roles as $rol)
                            <option value="{{ $rol->ID_rol }}" @selected(old('ID_rol') == $rol->ID_rol)>{{ $rol->Nombre_rol }}</option>
                        @endforeach
                    </select>
                    <p class="ayuda">Para ver qué puede hacer cada rol, abra <a href="{{ route('roles.permisos') }}">Permisos</a>.</p>
                    <button type="submit">Aprobar y activar</button>
                </form>

                <form method="post" action="{{ route('usuarios.rechazar', $usuario->ID_usuario) }}"
                      onsubmit="return confirm('¿Rechazar la solicitud de {{ addslashes($usuario->Nombre) }}?');">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="boton-claro">Rechazar solicitud</button>
                </form>
            </div>
        </div>
    @else
        <p><a href="{{ route('usuarios.index') }}">← Volver a usuarios</a></p>

        <h2>Usuarios pendientes</h2>
        <p class="subtitulo">Personas que se registraron y esperan que les asignen un rol.</p>

        @if($usuarios->isEmpty())
            <p class="vacio">No hay solicitudes pendientes.</p>
        @else
            <ul class="tarjetas">
                @foreach($usuarios as $pendiente)
                    <li class="tarjeta">
                        <span class="tarjeta__nombre">{{ $pendiente->Nombre }}</span>
                        <span class="tarjeta__dato">{{ $pendiente->Correo }}</span>
                        <span class="tarjeta__dato">{{ $pendiente->Nombre_organizacion ?? 'Sin organización' }}</span>
                        @can('USUARIOS.EDITAR')
                            <div class="fila-acciones">
                                <a class="boton boton-chico" href="{{ route('usuarios.aprobar.form', $pendiente->ID_usuario) }}">Revisar y aprobar</a>
                            </div>
                        @endcan
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
@endsection
