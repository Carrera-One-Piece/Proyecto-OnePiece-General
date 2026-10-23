{{--
    Matriz de permisos (RF27): qué puede hacer cada rol, módulo por módulo.
    Solo quien tiene USUARIOS.EDITAR puede cambiarla; los demás la ven con
    las casillas bloqueadas. El nombre técnico (TRIPULACIONES, RECOMPENSAS...)
    se deja debajo porque es el que se usa en el código: permiso:MODULO.ACCION.
--}}
@extends('layouts.panel')

@section('titulo', 'Matriz de permisos')

@php
    // Nombre legible de cada módulo (los que no estén aquí salen tal cual).
    $nombresModulo = [
        'TRIPULACIONES' => 'Tripulaciones',
        'RECOMPENSAS' => 'Recompensas',
        'ORDENES_CAPTURA' => 'Órdenes de captura',
        'NAVEGACION' => 'Navegación y rutas',
        'EVENTOS' => 'Eventos',
        'ALIANZAS' => 'Alianzas',
        'AMENAZAS' => 'Análisis de amenazas',
        'INFORMES' => 'Informes de inteligencia',
        'DASHBOARD' => 'Dashboard',
        'USUARIOS' => 'Usuarios, roles y permisos',
        'BITACORA' => 'Bitácora de accesos',
    ];

    $nombresAccion = ['VER' => 'Ver', 'CREAR' => 'Crear', 'EDITAR' => 'Editar', 'ELIMINAR' => 'Eliminar'];

    // Ver, Crear, Editar, Eliminar en ese orden; si hay otras, van al final.
    $columnas = collect(array_keys($nombresAccion))->intersect($acciones)
        ->merge($acciones->diff(array_keys($nombresAccion)))->values();

    $puedeEditar = auth()->user()->can('USUARIOS.EDITAR');
@endphp

@section('contenido')
    <h2>Matriz de permisos</h2>
    <p class="subtitulo">Escoja un rol para ver qué puede hacer en cada módulo.</p>

    <ul class="pestanas">
        @foreach($roles as $rol)
            <li>
                <a href="{{ route('roles.permisos', ['rol' => $rol->ID_rol]) }}"
                   @class(['activo' => $rolSeleccionado?->ID_rol === $rol->ID_rol])>
                    {{ $rol->Nombre_rol }}<small>{{ $rol->permisos_count }}</small>
                </a>
            </li>
        @endforeach
    </ul>

    @if(! $rolSeleccionado)
        <p class="vacio">Ningún rol escogido todavía.</p>
    @else
        <form method="post" action="{{ route('roles.permisos.update') }}">
            @csrf
            <input type="hidden" name="ID_rol" value="{{ $rolSeleccionado->ID_rol }}">

            <table class="tabla matriz">
                <thead>
                    <tr>
                        <th>Módulo</th>
                        @foreach($columnas as $accion)
                            <th>{{ $nombresAccion[$accion] ?? $accion }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($matriz as $modulo => $permisosModulo)
                        <tr>
                            <td>
                                <strong>{{ $nombresModulo[$modulo] ?? $modulo }}</strong>
                                <span class="codigo">{{ $modulo }}</span>
                            </td>
                            @foreach($columnas as $accion)
                                <td>
                                    @if(isset($permisosModulo[$accion]))
                                        @php($permiso = $permisosModulo[$accion])
                                        <input type="checkbox" name="permisos[]" value="{{ $permiso->ID_permiso }}"
                                               title="{{ $permiso->Descripcion ?? $permiso->Nombre_permiso }}"
                                               aria-label="{{ $permiso->Nombre_permiso }}"
                                               @checked($permisosAsignados->contains($permiso->ID_permiso))
                                               @disabled(! $puedeEditar)>
                                    @else
                                        <span class="no-aplica" title="Este módulo no tiene esa acción">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="barra-guardar">
                @if($puedeEditar)
                    <p>Los cambios se aplican a <strong>todos</strong> los usuarios con el rol {{ $rolSeleccionado->Nombre_rol }}.</p>
                    <button type="submit">Guardar cambios</button>
                @else
                    <p>Solo el Experto en Seguridad puede cambiar los permisos. Usted puede consultarlos.</p>
                @endif
            </div>
        </form>
    @endif
@endsection
