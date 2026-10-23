{{--
    Aumentar la recompensa (rebanada B). Va dentro de la ficha del pirata:
        @include('recompensas.partes.aumentar', ['recompensa' => $recompensa])
    Pide el valor NUEVO (no cuánto sumar) y el motivo. La casilla arranca con
    el valor actual, para que solo haya que cambiarlo.
--}}
<form class="panel" method="post" action="{{ route('recompensas.aumentar', $recompensa->ID_recompensa) }}">
    @csrf
    <h3>Aumentar</h3>

    <label for="aumentar-valor">Valor nuevo (berries)</label>
    <input type="text" inputmode="numeric" data-berries id="aumentar-valor" name="Valor"
           value="{{ old('Valor', number_format((float) $recompensa->Valor, 0, ',', '.')) }}" required>
    <p class="ayuda">Actual: {{ \App\Models\Recompensa::berries($recompensa->Valor) }}. El nuevo tiene que ser mayor.</p>

    <label for="aumentar-motivo">Motivo</label>
    <input type="text" id="aumentar-motivo" name="Motivo" value="{{ old('Motivo') }}" maxlength="255" required
           placeholder="Ej.: Incidente de Egghead">

    <button type="submit">Aumentar recompensa</button>
</form>
