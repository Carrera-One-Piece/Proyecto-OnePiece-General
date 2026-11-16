{{--
    Solicitud de alistamiento (registro).
    NO hay selector de rol a propósito: todo el que se registra queda
    Pendiente y sin rol hasta que el Experto en Seguridad lo apruebe.
--}}
@extends('layouts.auth')

@php
    $elegida = $organizaciones->firstWhere('ID_organizacion', (int) old('ID_organizacion'));
@endphp

@section('titulo', 'Solicitud de alistamiento')
@section('tema', $elegida?->tema() ?? 'tema-marina')

@section('contenido')
    <h2>Solicitud de Alistamiento</h2>
    <p class="subtitulo">Su solicitud será revisada antes de concederle acceso</p>

    @if ($errors->any())
        <div class="error" role="alert">
            <ul>
                @foreach ($errors->all() as $mensaje)
                    <li>{{ $mensaje }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('registro') }}" method="post">
        @csrf

        <label for="Nombre">Nombre y rango</label>
        <input type="text" id="Nombre" name="Nombre" required maxlength="150"
               placeholder="Ej.: Capitán Nombre Apellido" value="{{ old('Nombre') }}">

        <label for="Correo">Correo institucional</label>
        <input type="email" id="Correo" name="Correo" required maxlength="150"
               placeholder="nombre@organizacion.gov" value="{{ old('Correo') }}">

        <label for="ID_organizacion">Organización</label>
        <select id="ID_organizacion" name="ID_organizacion" required>
            <option value="" disabled @selected(! $elegida)>— Seleccione su organización —</option>
            @foreach ($organizaciones as $organizacion)
                <option value="{{ $organizacion->ID_organizacion }}"
                        data-tema="{{ $organizacion->tema() }}"
                        data-desc="{{ $organizacion->descripcion() }}"
                        @selected($elegida?->ID_organizacion === $organizacion->ID_organizacion)>
                    {{ $organizacion->Nombre }}
                </option>
            @endforeach
        </select>
        <p class="descripcion-org" id="descripcion-org">{{ $elegida?->descripcion() }}</p>

        <label for="Contrasena">Clave de acceso (mín. 8 caracteres)</label>
        <input type="password" id="Contrasena" name="Contrasena" required minlength="8">

        <label for="Contrasena_confirmation">Confirmar clave</label>
        <input type="password" id="Contrasena_confirmation" name="Contrasena_confirmation" required minlength="8">

        <button type="submit">Enviar solicitud</button>
    </form>

    <p class="enlace-extra">
        ¿Ya tiene credenciales?
        <a href="{{ route('login') }}">Ingresar</a>
    </p>
@endsection

@push('scripts')
    {{-- Solo estético: cambia los colores según la organización. La validación real está en el servidor. --}}
    <script>
        const selector = document.getElementById('ID_organizacion');
        const descripcion = document.getElementById('descripcion-org');
        const temas = ['tema-marina', 'tema-cp', 'tema-gobierno'];

        selector.addEventListener('change', () => {
            const opcion = selector.options[selector.selectedIndex];
            document.body.classList.remove(...temas);
            document.body.classList.add(opcion.dataset.tema);
            descripcion.textContent = opcion.dataset.desc;
        });
    </script>
@endpush
