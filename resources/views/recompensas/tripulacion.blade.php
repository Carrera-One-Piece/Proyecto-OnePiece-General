{{--
    Página de la tripulación (rebanada A): su bandera, sus miembros con el
    rol de cada uno y su recompensa, y el total sumado. Solo lectura.
--}}
@extends('layouts.panel')

@use('App\Models\Recompensa')
@use('App\Support\ImagenesRecompensas')

@section('titulo', $tripulacion->Nombre)

@section('contenido')
    <p><a href="{{ route('recompensas.index') }}">← Volver al tablón</a></p>

    <div class="cabecera-tripulacion">
        <img src="{{ ImagenesRecompensas::tripulacion($tripulacion) }}" alt="Bandera de {{ $tripulacion->Nombre }}"
             onerror="this.onerror=null;this.src='{{ asset(ImagenesRecompensas::POR_DEFECTO) }}'">
        <div>
            <h2>{{ $tripulacion->Nombre }}</h2>
            <p class="subtitulo">Tripulación {{ strtolower($tripulacion->Estado ?? 'sin estado') }} · {{ $miembros->count() }} miembros registrados</p>
            <div>Recompensa total de la tripulación</div>
            <div class="total">{{ Recompensa::berries($total) }}</div>
        </div>
    </div>

    @if($miembros->isEmpty())
        <p class="vacio">Esta tripulación no tiene miembros registrados.</p>
    @else
        <table class="tabla">
            <thead>
                <tr>
                    <th></th>
                    <th>Pirata</th>
                    <th>Rol</th>
                    <th class="numero">Recompensa actual</th>
                </tr>
            </thead>
            <tbody>
                @foreach($miembros as $miembro)
                    <tr>
                        <td><img class="mini" src="{{ ImagenesRecompensas::personaje($miembro) }}" alt=""></td>
                        <td>
                            <a href="{{ route('recompensas.pirata', $miembro->ID_personaje) }}"><strong>{{ $miembro->Nombre }}</strong></a>
                            @if($miembro->Alias)<br><small>«{{ $miembro->Alias }}»</small>@endif
                        </td>
                        <td>{{ $miembro->rol_en_tripulacion ?? 'Miembro' }}</td>
                        <td class="numero">
                            {{ $miembro->recompensaActual ? Recompensa::berries($miembro->recompensaActual->Valor) : 'Sin recompensa activa' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">Total (solo recompensas activas)</td>
                    <td class="numero">{{ Recompensa::berries($total) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif
@endsection
