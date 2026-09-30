<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Matriz de permisos</title>
</head>

<body>

<h1>Matriz de permisos</h1>

<p>
    <a href="{{ route('inicio') }}">Volver al inicio</a>
    ·
    <a href="{{ route('usuarios.index') }}">Administrar usuarios</a>
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

<form
    method="GET"
    action="{{ route('roles.permisos') }}"
>

    <label for="rol">
        Seleccionar rol:
    </label>

    <select
        name="rol"
        id="rol"
        onchange="this.form.submit()"
    >

        <option value="">
            Seleccione un rol
        </option>

        @foreach($roles as $rol)

            <option
                value="{{ $rol->ID_rol }}"
                {{
                    $rolSeleccionado &&
                    $rolSeleccionado->ID_rol == $rol->ID_rol
                    ? 'selected'
                    : ''
                }}
            >
                {{ $rol->Nombre_rol }}
            </option>

        @endforeach

    </select>

</form>

@if($rolSeleccionado)

    <h2>
        Rol seleccionado:
        {{ $rolSeleccionado->Nombre_rol }}
    </h2>

    <form
        method="POST"
        action="{{ route('roles.permisos.update') }}"
    >

        @csrf

        <input
            type="hidden"
            name="ID_rol"
            value="{{ $rolSeleccionado->ID_rol }}"
        >

        <table border="1" cellpadding="8">

            <thead>

                <tr>
                    <th>Módulo</th>

                    @foreach($acciones as $accion)

                        <th>
                            {{ $accion }}
                        </th>

                    @endforeach

                </tr>

            </thead>

            <tbody>

                @foreach($matriz as $modulo => $permisosModulo)

                    <tr>

                        <td>
                            <strong>
                                {{ $modulo }}
                            </strong>
                        </td>

                        @foreach($acciones as $accion)

                            <td>

                                @if(isset($permisosModulo[$accion]))

                                    @php
                                        $permiso =
                                            $permisosModulo[$accion];
                                    @endphp

                                    <input
                                        type="checkbox"
                                        name="permisos[]"
                                        value="{{ $permiso->ID_permiso }}"
                                        {{
                                            $permisosAsignados->contains(
                                                $permiso->ID_permiso
                                            )
                                            ? 'checked'
                                            : ''
                                        }}
                                    >

                                @else

                                    —

                                @endif

                            </td>

                        @endforeach

                    </tr>

                @endforeach

            </tbody>

        </table>

        <br>

        <button type="submit">
            Guardar cambios
        </button>

    </form>

@endif

</body>
</html>