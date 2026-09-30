<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar usuarios</title>
</head>

<body>

<h1>Administrar usuarios</h1>

<p>
    <a href="{{ route('inicio') }}">Volver al inicio</a>
    ·
    <a href="{{ route('roles.permisos') }}">Matriz de permisos</a>
</p>

@if(session('success'))
    <div>
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div>
        {{ session('error') }}
    </div>
@endif

<h2>Filtros</h2>

<form
    method="GET"
    action="{{ route('usuarios.index') }}"
>

    <label for="estado">
        Estado:
    </label>

    <select name="estado" id="estado">

        <option value="">
            Todos
        </option>

        <option
            value="Pendiente"
            {{ request('estado') === 'Pendiente' ? 'selected' : '' }}
        >
            Pendiente
        </option>

        <option
            value="Activo"
            {{ request('estado') === 'Activo' ? 'selected' : '' }}
        >
            Activo
        </option>

        <option
            value="Suspendido"
            {{ request('estado') === 'Suspendido' ? 'selected' : '' }}
        >
            Suspendido
        </option>

        <option
            value="Inactivo"
            {{ request('estado') === 'Inactivo' ? 'selected' : '' }}
        >
            Inactivo
        </option>

    </select>

    <label for="rol">
        Rol:
    </label>

    <select name="rol" id="rol">

        <option value="">
            Todos
        </option>

        @foreach($roles as $rol)

            <option
                value="{{ $rol->ID_rol }}"
                {{ request('rol') == $rol->ID_rol ? 'selected' : '' }}
            >
                {{ $rol->Nombre_rol }}
            </option>

        @endforeach

    </select>

    <button type="submit">
        Filtrar
    </button>

    <a href="{{ route('usuarios.index') }}">
        Limpiar
    </a>

</form>

<br>

<a href="{{ route('usuarios.pendientes') }}">
    Ver pendientes
</a>

<table border="1" cellpadding="8">

    <thead>

        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Organización</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

    </thead>

    <tbody>

        @forelse($usuarios as $usuario)

            <tr>

                <td>
                    {{ $usuario->ID_usuario }}
                </td>

                <td>
                    {{ $usuario->Nombre }}
                </td>

                <td>
                    {{ $usuario->Correo }}
                </td>

                <td>
                    {{ $usuario->Nombre_organizacion ?? 'Sin organización' }}
                </td>

                <td>
                    {{ $usuario->Nombre_rol ?? 'Sin rol' }}
                </td>

                <td>
                    {{ $usuario->Estado }}
                </td>

                <td>

                    @if($usuario->Estado === 'Pendiente')

                        <a href="{{ route(
                            'usuarios.aprobar.form',
                            $usuario->ID_usuario
                        ) }}">
                            Aprobar
                        </a>

                        <form
                            method="POST"
                            action="{{ route(
                                'usuarios.rechazar',
                                $usuario->ID_usuario
                            ) }}"
                            style="display:inline"
                        >

                            @csrf
                            @method('PATCH')

                            <button type="submit">
                                Rechazar
                            </button>

                        </form>

                    @endif

                    @if($usuario->Estado === 'Activo')

                        <form
                            method="POST"
                            action="{{ route(
                                'usuarios.suspender',
                                $usuario->ID_usuario
                            ) }}"
                        >

                            @csrf
                            @method('PATCH')

                            <button type="submit">
                                Suspender
                            </button>

                        </form>

                    @endif

                </td>

            </tr>

        @empty

            <tr>
                <td colspan="7">
                    No hay usuarios.
                </td>
            </tr>

        @endforelse

    </tbody>

</table>

<br>

{{ $usuarios->links('pagination::default') }}

</body>
</html>