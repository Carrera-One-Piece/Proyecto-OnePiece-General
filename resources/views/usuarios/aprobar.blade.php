<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Aprobar usuario</title>
</head>

<body>

@if($usuario)

    <h1>Aprobar usuario</h1>

    <p>
        <strong>Nombre:</strong>
        {{ $usuario->Nombre }}
    </p>

    <p>
        <strong>Correo:</strong>
        {{ $usuario->Correo }}
    </p>

    <p>
        <strong>Organización:</strong>
        {{ $usuario->Nombre_organizacion ?? 'Sin organización' }}
    </p>

    <p>
        <strong>Estado:</strong>
        {{ $usuario->Estado }}
    </p>

    <form
        method="POST"
        action="{{ route(
            'usuarios.aprobar',
            $usuario->ID_usuario
        ) }}"
    >

        @csrf

        <label for="ID_rol">
            Asignar rol:
        </label>

        <select
            name="ID_rol"
            id="ID_rol"
            required
        >

            <option value="">
                Seleccione un rol
            </option>

            @foreach($roles as $rol)

                <option value="{{ $rol->ID_rol }}">
                    {{ $rol->Nombre_rol }}
                </option>

            @endforeach

        </select>

        <br><br>

        <button type="submit">
            Aprobar usuario
        </button>

    </form>

    <br>

    <a href="{{ route('usuarios.pendientes') }}">
        Volver
    </a>

@else

    <h1>Usuarios pendientes</h1>

    @forelse($usuarios as $usuarioPendiente)

        <div>

            <strong>
                {{ $usuarioPendiente->Nombre }}
            </strong>

            <br>

            {{ $usuarioPendiente->Correo }}

            <br>

            Organización:
            {{ $usuarioPendiente->Nombre_organizacion ?? 'Sin organización' }}

            <br><br>

            <a href="{{ route(
                'usuarios.aprobar.form',
                $usuarioPendiente->ID_usuario
            ) }}">
                Seleccionar
            </a>

        </div>

        <hr>

    @empty

        <p>
            No hay usuarios pendientes.
        </p>

    @endforelse

    <a href="{{ route('usuarios.index') }}">
        Volver a usuarios
    </a>

@endif

</body>
</html>