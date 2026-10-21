<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// Asegúrate de que el modelo de Eventos exista (o lo crearemos si falta)
use App\Models\Evento; 

class EventoController extends Controller
{
    // Constructor para aplicar los middlewares de permisos que pidió tu equipo
    public function __construct()
    {
        $this->middleware('permiso:EVENTOS.VER')->only(['index', 'show']);
        $this->middleware('permiso:EVENTOS.CREAR')->only(['store']);
        $this->middleware('permiso:EVENTOS.EDITAR')->only(['update']);
        $this->middleware('permiso:EVENTOS.ELIMINAR')->only(['destroy']);
    }

    // 1. VER / LISTAR EVENTOS
    public function index()
    {
        return response()->json(Evento::all(), 200);
    }

    // 2. CREAR EVENTO
    public function store(Request $request)
    {
        // Validar que el tipo de evento venga de 'tipos_evento' según las indicaciones
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo_evento_id' => 'required|exists:tipos_evento,id', // Validando contra tipos_evento
        ]);

        $evento = Evento::create($request->all());

        return response()->json([
            'message' => 'Evento creado con éxito',
            'data' => $evento
        ], 201);
    }

    // 3. EDITAR / ACTUALIZAR EVENTO
    public function update(Request $request, $id)
    {
        $evento = Evento::findOrFail($id);

        $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'tipo_evento_id' => 'sometimes|required|exists:tipos_evento,id',
        ]);

        $evento->update($request->all());

        return response()->json([
            'message' => 'Evento actualizado con éxito',
            'data' => $evento
        ], 200);
    }

    // 4. ELIMINAR EVENTO
    public function destroy($id)
    {
        $evento = Evento::findOrFail($id);
        $evento->delete();

        return response()->json([
            'message' => 'Evento eliminado con éxito'
        ], 200);
    }
}