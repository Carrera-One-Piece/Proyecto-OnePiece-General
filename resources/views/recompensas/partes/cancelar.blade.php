{{--
    Cancelar la recompensa (rebanada B). Va dentro de la ficha del pirata:
        @include('recompensas.partes.cancelar', ['recompensa' => $recompensa])
    Cambia el Estado a "Cancelada - Capturado" o "Cancelada - Muerto". Nunca borra.
--}}
@use('App\Models\Recompensa')

<form class="panel panel--peligro" method="post" action="{{ route('recompensas.cancelar', $recompensa->ID_recompensa) }}"
      onsubmit="return confirm('¿Cancelar la recompensa de {{ addslashes($recompensa->personaje?->Nombre ?? 'este pirata') }}?');">
    @csrf
    <h3>Cancelar</h3>

    <label for="cancelar-estado">Motivo de la cancelación</label>
    <select id="cancelar-estado" name="Estado" required>
        @foreach(Recompensa::ESTADOS_CANCELADA as $estado => $texto)
            <option value="{{ $estado }}" @selected(old('Estado') === $estado)>{{ $texto }}</option>
        @endforeach
    </select>
    <p class="ayuda">La recompensa no se borra: queda en el historial como cancelada.</p>

    <button type="submit" class="boton-peligro">Cancelar recompensa</button>
</form>
