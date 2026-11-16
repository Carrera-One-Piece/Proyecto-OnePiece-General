<?php

namespace App\Http\Controllers;

use App\Models\Tripulacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripulacionController extends Controller
{
    // REGISTRAR (RF01)
    public function store(Request $request)
    {
        $datos = $request->validate([
            'Nombre'       => 'required|string|max:150',
            'Estado'       => 'required|string|max:50',
            'ID_capitan'   => 'required|exists:personajes,ID_personaje',
            'barco_nombre' => 'required|string|max:150',
            'barco_tipo'   => 'nullable|string|max:80',
        ]);

        $tripulacion = DB::transaction(function () use ($datos) {
            $t = Tripulacion::create([
                'Nombre' => $datos['Nombre'],
                'Estado' => $datos['Estado'],
            ]);

            DB::table('tripulacion_personaje')->insert([
                'ID_tripulacion' => $t->ID_tripulacion,
                'ID_personaje'   => $datos['ID_capitan'],
                'Rol'            => 'Capitán',
            ]);

            DB::table('barcos')->insert([
                'Nombre'         => $datos['barco_nombre'],
                'Tipo'           => $datos['barco_tipo'] ?? null,
                'ID_tripulacion' => $t->ID_tripulacion,
            ]);

            return $t;
        });

        return response()->json($tripulacion, 201);
    }

    // VER TODAS (Read)
    public function index()
    {
        return Tripulacion::where('Estado', '!=', 'Disuelta')->get();
    }

    // VER UNA CON SUS DATOS (Read)
    public function show($id)
    {
        $t = Tripulacion::findOrFail($id);

        $miembros = DB::table('tripulacion_personaje as tp')
            ->join('personajes as p', 'p.ID_personaje', '=', 'tp.ID_personaje')
            ->where('tp.ID_tripulacion', $id)
            ->get(['p.Nombre', 'tp.Rol']);

        $barcos = DB::table('barcos')->where('ID_tripulacion', $id)->get();

        return response()->json([
            'tripulacion' => $t,
            'miembros'    => $miembros,
            'barcos'      => $barcos,
        ]);
    }

    // MODIFICAR (Update)
    public function update(Request $request, $id)
    {
        $t = Tripulacion::findOrFail($id);
        $t->update($request->only([
            'Nombre', 'Estado', 'Bounty_general', 'URL_bandera', 'URL_imagen',
        ]));
        return response()->json($t);
    }

    // BAJA LÓGICA (RF04): no borra, solo cambia el Estado
    public function destroy($id)
    {
        Tripulacion::findOrFail($id)->update([
            'Estado' => 'Disuelta',
        ]);
        return response()->json(['mensaje' => 'Tripulación dada de baja']);
    }
}