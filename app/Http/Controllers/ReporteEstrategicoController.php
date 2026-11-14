<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReporteEstrategicoController extends Controller
{
    public function index(): View
    {
        $datos = DB::transaction(function () {
            $tripulaciones = DB::table('tripulaciones')
                ->select(
                    'ID_tripulacion',
                    'Nombre',
                    'Estado',
                    'Bounty_general'
                )
                ->orderBy('Nombre')
                ->get();

            $recompensas = DB::table('recompensas')
                ->select(
                    'ID_recompensa',
                    'ID_personaje',
                    'Valor',
                    'Motivo',
                    'Fecha',
                    'Estado'
                )
                ->orderByDesc('Fecha')
                ->orderByDesc('ID_recompensa')
                ->get();

            $movimientos = DB::table('movimiento_barco')
                ->select(
                    'ID_movimiento_barco',
                    'ID_barco',
                    'Coordenada_X',
                    'Coordenada_Y',
                    'Fecha_registro'
                )
                ->orderByDesc('Fecha_registro')
                ->orderByDesc('ID_movimiento_barco')
                ->get();

            $eventos = DB::table('eventos')
                ->select(
                    'ID_evento',
                    'Nombre',
                    'ID_isla',
                    'Fecha_evento',
                    'Descripcion',
                    'Nivel_amenaza'
                )
                ->orderByDesc('Fecha_evento')
                ->orderByDesc('ID_evento')
                ->get();

            $amenazas = DB::table('analisis_amenaza')
                ->select(
                    'ID_analisis',
                    'ID_personaje',
                    'ID_relacion',
                    'ID_territorio',
                    'Responsable',
                    'Nivel_amenaza',
                    'Fecha_analisis'
                )
                ->orderByDesc('Fecha_analisis')
                ->orderByDesc('ID_analisis')
                ->get();

            $conexiones = DB::table('isla_adyacente as conexion')
                ->leftJoin(
                    'isla as origen',
                    'conexion.ID_isla_origen',
                    '=',
                    'origen.ID_isla'
                )
                ->leftJoin(
                    'isla as destino',
                    'conexion.ID_isla_destino',
                    '=',
                    'destino.ID_isla'
                )
                ->select(
                    'conexion.ID_adyacencia',
                    'conexion.ID_isla_origen',
                    'conexion.ID_isla_destino',
                    'origen.Nombre as Isla_origen',
                    'destino.Nombre as Isla_destino',
                    'conexion.Distancia',
                    'conexion.Tiempo_estimado',
                    'conexion.Observaciones'
                )
                ->orderBy('origen.Nombre')
                ->orderBy('destino.Nombre')
                ->get();

            return compact(
                'tripulaciones',
                'recompensas',
                'movimientos',
                'eventos',
                'amenazas',
                'conexiones'
            );
        });

        $datos['generadoEn'] = now();

        return view('inteligencia.estrategico.index', $datos);
    }
}