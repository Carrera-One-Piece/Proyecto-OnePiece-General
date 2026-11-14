<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\ReporteInteligencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReporteInteligenciaController extends Controller
{
    public function index(): View
    {
        $reportes = ReporteInteligencia::query()
            ->orderByDesc('Fecha_reporte')
            ->orderByDesc('ID_reporte')
            ->paginate(10);

        return view('inteligencia.reportes.index', compact('reportes'));
    }

    public function create(): View
    {
        $informantes = DB::table('personajes')
            ->orderBy('ID_personaje')
            ->get();

        return view('inteligencia.reportes.create', compact('informantes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'Titulo' => ['required', 'string', 'max:150'],
            'Contenido' => ['required', 'string'],
            'Fuente' => ['required', 'string', 'max:150'],
            'Confiabilidad' => [
                'required',
                Rule::in(['Alta', 'Media', 'Baja']),
            ],
            'ID_informante' => [
                'nullable',
                'integer',
                'exists:personajes,ID_personaje',
            ],
        ], [
            'Titulo.required' => 'Escribe el título del reporte.',
            'Titulo.max' => 'El título no puede superar los 150 caracteres.',
            'Contenido.required' => 'Escribe el contenido del reporte.',
            'Fuente.required' => 'Indica la fuente de origen.',
            'Fuente.max' => 'La fuente no puede superar los 150 caracteres.',
            'Confiabilidad.required' => 'Selecciona la confiabilidad.',
            'Confiabilidad.in' => 'Selecciona Alta, Media o Baja.',
            'ID_informante.exists' => 'El informante seleccionado no existe.',
        ]);

        $datos['Fecha_reporte'] = now();

        DB::transaction(function () use ($datos, $request) {
    $reporte = ReporteInteligencia::create($datos);

    Bitacora::create([
        'ID_usuario' => $request->user()->ID_usuario,
        'Correo_intento' => $request->user()->Correo,
        'Accion' => Bitacora::REPORTE_INTELIGENCIA_CREADO,
        'IP' => $request->ip(),
        'Detalle' => 'Reporte de inteligencia #' . $reporte->ID_reporte,
        'Fecha' => now(),
    ]);
});

        return redirect()
            ->route('inteligencia.reportes.index')
            ->with('exito', 'Reporte de inteligencia registrado correctamente.');
    }
}