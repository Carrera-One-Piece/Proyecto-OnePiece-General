<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Bitácora de accesos</title>
</head>

<body>

<h1>Bitácora de accesos</h1>

<p>
    <a href="{{ route('inicio') }}">Volver al inicio</a>
</p>

@if ($errors->any())
    <div>{{ $errors->first() }}</div>
@endif

<h2>Filtros</h2>

<form method="GET" action="{{ route('bitacora.index') }}">

    <label for="correo">Correo:</label>
    <input type="text" id="correo" name="correo" value="{{ request('correo') }}">

    <label for="accion">Acción:</label>
    <select id="accion" name="accion">
        <option value="">Todas</option>
        @foreach ($acciones as $accion)
            <option value="{{ $accion }}" @selected(request('accion') === $accion)>{{ $accion }}</option>
        @endforeach
    </select>

    <label for="desde">Desde:</label>
    <input type="date" id="desde" name="desde" value="{{ request('desde') }}">

    <label for="hasta">Hasta:</label>
    <input type="date" id="hasta" name="hasta" value="{{ request('hasta') }}">

    <button type="submit">Filtrar</button>
    <a href="{{ route('bitacora.index') }}">Limpiar</a>

</form>

<br>

<table border="1" cellpadding="8">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Acción</th>
            <th>Correo</th>
            <th>ID usuario</th>
            <th>IP</th>
            <th>Detalle</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($registros as $registro)
            <tr>
                <td>{{ $registro->Fecha?->format('Y-m-d H:i:s') }}</td>
                <td>{{ $registro->Accion }}</td>
                <td>{{ $registro->Correo_intento ?? '—' }}</td>
                <td>{{ $registro->ID_usuario ?? '—' }}</td>
                <td>{{ $registro->IP ?? '—' }}</td>
                <td>{{ $registro->Detalle ?? '' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6">No hay registros.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<br>

{{ $registros->links('pagination::default') }}

</body>
</html>
