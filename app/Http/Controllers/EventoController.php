<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use Illuminate\Http\Request;

/**
 * Módulo Eventos (RF13-RF15).
 *
 * Los permisos NO se ponen aquí: se aplican en routes/web.php con
 * ->middleware('permiso:EVENTOS.VER' | CREAR | EDITAR | ELIMINAR),
 * como indica docs/guia-para-otros-subgrupos.md.
 */
class EventoController extends Controller
{
    // GET /eventos
    public function index()
    {
        return response()->json(Evento::all(), 200);
    }

    // GET /eventos/{id}
    public function show($id)
    {
        return response()->json(Evento::findOrFail($id), 200);
    }

    // POST /eventos
    public function store(Request $request)
    {
        $datos = $request->validate([
            'Nombre'         => ['required', 'string', 'max:150'],
            'ID_tipo_evento' => ['required', 'integer', 'exists:tipos_evento,ID_tipo_evento'],
            'ID_isla'        => ['required', 'integer', 'exists:isla,ID_isla'],
            'Fecha_evento'   => ['nullable', 'date'],
            'Descripcion'    => ['nullable', 'string'],
            'Nivel_amenaza'  => ['nullable', 'string', 'max:50'],
        ]);

        $evento = Evento::create($datos);

        return response()->json([
            'message' => 'Evento creado con éxito',
            'data'    => $evento,
        ], 201);
    }

    // PUT/PATCH /eventos/{id}
    public function update(Request $request, $id)
    {
        $evento = Evento::findOrFail($id);

        $datos = $request->validate([
            'Nombre'         => ['sometimes', 'required', 'string', 'max:150'],
            'ID_tipo_evento' => ['sometimes', 'required', 'integer', 'exists:tipos_evento,ID_tipo_evento'],
            'ID_isla'        => ['sometimes', 'required', 'integer', 'exists:isla,ID_isla'],
            'Fecha_evento'   => ['sometimes', 'nullable', 'date'],
            'Descripcion'    => ['sometimes', 'nullable', 'string'],
            'Nivel_amenaza'  => ['sometimes', 'nullable', 'string', 'max:50'],
        ]);

        $evento->update($datos);

        return response()->json([
            'message' => 'Evento actualizado con éxito',
            'data'    => $evento,
        ], 200);
    }

    // DELETE /eventos/{id}
    public function destroy($id)
    {
        Evento::findOrFail($id)->delete();

        return response()->json(['message' => 'Evento eliminado con éxito'], 200);
    }
}