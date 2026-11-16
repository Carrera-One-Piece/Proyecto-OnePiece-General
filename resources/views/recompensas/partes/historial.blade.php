{{--
    Historial de la recompensa (RF08 / PB09). Va dentro de la ficha del pirata
    (rebanada A). Uso en recompensas/pirata.blade.php:

        @include('recompensas.partes.historial', ['personaje' => $personaje])

    Opcional: si el controlador ya trae las filas, se pueden pasar como
    'historial' => $historial y aquí no se vuelven a consultar.

    Las filas INSERT / UPDATE / DELETE las escribe la base (triggers).
    La de CANCELAR la escribe Laravel mientras el CTO arregla el trigger.
--}}
@php
    $historial = $historial
        ?? \App\Models\AuditoriaRecompensa::delPersonaje($personaje->ID_personaje)->get();

    $berries = fn ($valor) => is_null($valor)
        ? '—'
        : '฿ ' . number_format((float) $valor, 0, ',', '.');
@endphp

<section class="historial-recompensa">
    <h2 class="historial-recompensa__titulo">Historial de la recompensa</h2>

    @if($historial->isEmpty())
        <p class="historial-recompensa__vacio">Sin cambios registrados.</p>
    @else
        <table class="historial-recompensa__tabla">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Operación</th>
                    <th>Valor anterior</th>
                    <th></th>
                    <th>Valor nuevo</th>
                </tr>
            </thead>
            <tbody>
                @foreach($historial as $fila)
                    <tr class="historial-recompensa__fila historial-recompensa__fila--{{ strtolower($fila->Operacion) }}">
                        <td>{{ $fila->Fecha_Modificacion?->format('d/m/Y H:i') }}</td>
                        <td>
                            {{ $fila->operacion_es }}
                            @if($fila->Operacion === 'CANCELAR' && $fila->recompensa)
                                <small>({{ str_replace('Cancelada - ', '', $fila->recompensa->Estado) }})</small>
                            @endif
                        </td>
                        <td>{{ $berries($fila->Valor_Anterior) }}</td>
                        <td aria-hidden="true">→</td>
                        <td>{{ $berries($fila->Valor_Nuevo) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</section>

@once
<style>
    .historial-recompensa { margin-top: 2rem; }
    .historial-recompensa__titulo {
        font-size: 1.25rem;
        letter-spacing: .05em;
        text-transform: uppercase;
        border-bottom: 2px solid currentColor;
        padding-bottom: .25rem;
        margin-bottom: .75rem;
    }
    .historial-recompensa__vacio { font-style: italic; opacity: .75; }
    .historial-recompensa__tabla { width: 100%; border-collapse: collapse; }
    .historial-recompensa__tabla th,
    .historial-recompensa__tabla td {
        padding: .5rem .75rem;
        text-align: left;
        border-bottom: 1px solid rgba(0, 0, 0, .15);
    }
    .historial-recompensa__tabla td:nth-child(3),
    .historial-recompensa__tabla td:nth-child(5) {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .historial-recompensa__fila--update   td:nth-child(2) { color: #1e7b34; font-weight: 600; }
    .historial-recompensa__fila--cancelar td:nth-child(2) { color: #a4161a; font-weight: 600; }
</style>
@endonce
