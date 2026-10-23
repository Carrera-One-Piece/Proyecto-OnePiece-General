{{--
    Ficha del pirata (rebanada A): cartel de "SE BUSCA", sus datos, su
    tripulación u organización, y debajo:
      - los botones de aumentar / cancelar / editar / borrar (rebanada B),
        solo para quien tiene el permiso (@can). Esconderlos NO es la
        seguridad: las rutas ya tienen su guardia.
      - el historial (rebanada C).
--}}
@extends('layouts.panel')

@use('App\Models\Recompensa')
@use('App\Support\ImagenesRecompensas')

@section('titulo', $personaje->Nombre)

@section('contenido')
    @php
        $tripulacion = $personaje->tripulaciones->first();
        $organizacion = $tripulacion ? null : $personaje->organizaciones->first();
        $ultima = $recompensa ?? $personaje->recompensas->first();
    @endphp

    <p><a href="{{ route('recompensas.index') }}">← Volver al tablón</a></p>

    <div class="ficha">
        <div class="cartel">
            <p class="cartel__titulo">SE BUSCA</p>
            <img class="cartel__foto" src="{{ ImagenesRecompensas::cartel($personaje) }}"
                 alt="Imagen de {{ $personaje->Nombre }}"
                 onerror="this.onerror=null;this.src='{{ asset(ImagenesRecompensas::POR_DEFECTO) }}'">
            <div class="cartel__condicion">VIVO O MUERTO</div>
            <p class="cartel__nombre">{{ $personaje->Nombre }}</p>
            <p class="cartel__valor">
                {{ $ultima ? Recompensa::berries($ultima->Valor) : '฿ —' }}
            </p>
            <div class="cartel__marina">Marina · Gobierno Mundial</div>

            @if($ultima && ! $ultima->estaActiva())
                <div class="cartel__sello">{{ strtoupper(Recompensa::ESTADOS_CANCELADA[$ultima->Estado] ?? $ultima->Estado) }}</div>
            @endif
        </div>

        <div>
            <h2>{{ $personaje->Nombre }}</h2>
            <p class="subtitulo">{{ $personaje->Alias ? '«'.$personaje->Alias.'»' : 'Sin alias conocido' }}</p>

            <dl class="datos">
                <dt>Estado del pirata</dt>
                <dd>{{ $personaje->Estado ?? 'Desconocido' }}</dd>

                <dt>Recompensa actual</dt>
                <dd>
                    @if($recompensa)
                        <strong>{{ Recompensa::berries($recompensa->Valor) }}</strong>
                        <span class="estado {{ $recompensa->claseEstado() }}">{{ $recompensa->Estado }}</span>
                    @else
                        <span class="vacio">Sin recompensa activa</span>
                    @endif
                </dd>

                @if($ultima)
                    <dt>Motivo</dt>
                    <dd>{{ $ultima->Motivo ?? '—' }}</dd>

                    <dt>Evento</dt>
                    <dd>{{ $ultima->evento?->Nombre ?? 'Ninguno registrado' }}</dd>

                    <dt>Emitida</dt>
                    <dd>{{ $ultima->Fecha ? \Illuminate\Support\Carbon::parse($ultima->Fecha)->format('d/m/Y') : '—' }}</dd>
                @endif
            </dl>

            @if($tripulacion || $organizacion)
                <div class="grupo">
                    <img src="{{ $tripulacion ? ImagenesRecompensas::tripulacion($tripulacion) : ImagenesRecompensas::organizacion($organizacion) }}"
                         alt="" onerror="this.onerror=null;this.src='{{ asset(ImagenesRecompensas::POR_DEFECTO) }}'">
                    <div>
                        @if($tripulacion)
                            <div class="grupo__nombre">
                                <a href="{{ route('recompensas.tripulacion', $tripulacion->ID_tripulacion) }}">{{ $tripulacion->Nombre }}</a>
                            </div>
                            <div class="grupo__rol">{{ $tripulacion->pivot->Rol ?? 'Miembro' }} · Tripulación {{ strtolower($tripulacion->Estado ?? '') }}</div>
                        @else
                            <div class="grupo__nombre">{{ $organizacion->Nombre }}</div>
                            <div class="grupo__rol">{{ $organizacion->pivot->Cargo ?? 'Miembro' }} · {{ $organizacion->Tipo }}</div>
                        @endif
                    </div>
                </div>
            @else
                <p class="vacio">No pertenece a ninguna tripulación ni organización registrada.</p>
            @endif

            {{-- Acciones de la rebanada B: solo con permiso. --}}
            @if($recompensa)
                @canany(['RECOMPENSAS.EDITAR', 'RECOMPENSAS.ELIMINAR'])
                    <div class="acciones">
                        @can('RECOMPENSAS.EDITAR')
                            @include('recompensas.partes.aumentar', ['recompensa' => $recompensa])
                            @include('recompensas.partes.cancelar', ['recompensa' => $recompensa])
                        @endcan
                    </div>
                @endcanany
            @else
                @can('RECOMPENSAS.CREAR')
                    <a class="boton boton-chico" href="{{ route('recompensas.crear', ['personaje' => $personaje->ID_personaje]) }}">+ Emitir recompensa</a>
                @endcan
            @endif

            @if($ultima)
                <div class="acciones-extra">
                    @can('RECOMPENSAS.EDITAR')
                        <a class="boton boton-chico boton-claro" href="{{ route('recompensas.editar', $ultima->ID_recompensa) }}">Corregir motivo o evento</a>
                    @endcan
                    @can('RECOMPENSAS.ELIMINAR')
                        <form method="post" action="{{ route('recompensas.eliminar', $ultima->ID_recompensa) }}"
                              onsubmit="return confirm('¿Borrar esta recompensa?\n\nSolo para recompensas creadas por error: el historial queda, pero ya no se sabrá de qué pirata era. Si lo capturaron o murió, use Cancelar.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="boton-chico boton-peligro">Borrar (creada por error)</button>
                        </form>
                    @endcan
                </div>
            @endif
        </div>
    </div>

    @include('recompensas.partes.historial', ['personaje' => $personaje])

    @if($personaje->recompensas->count() > 1)
        <section class="historial-recompensa">
            <h2 class="historial-recompensa__titulo">Todas sus recompensas</h2>
            <table class="tabla">
                <thead>
                    <tr><th>Emitida</th><th>Motivo</th><th>Estado</th><th class="numero">Valor</th></tr>
                </thead>
                <tbody>
                    @foreach($personaje->recompensas as $r)
                        <tr>
                            <td>{{ $r->Fecha ? \Illuminate\Support\Carbon::parse($r->Fecha)->format('d/m/Y') : '—' }}</td>
                            <td>{{ $r->Motivo }}</td>
                            <td><span class="estado {{ $r->claseEstado() }}">{{ $r->Estado }}</span></td>
                            <td class="numero">{{ Recompensa::berries($r->Valor) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
@endsection
