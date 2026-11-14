<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Models\InformeInteligencia;
use App\Models\ReporteInteligencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InformeInteligenciaController extends Controller
{
    public function index(): View
    {
        $informes = InformeInteligencia::query()
            ->withCount('reportes')
            ->orderByDesc('Fecha_consolidacion')
            ->orderByDesc('ID_informe')
            ->paginate(10);

        return view('inteligencia.informes.index', compact('informes'));
    }

    public function create(): View
    {
        $reportes = ReporteInteligencia::query()
            ->orderByDesc('Fecha_reporte')
            ->orderByDesc('ID_reporte')
            ->get();

        return view('inteligencia.informes.create', compact('reportes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'Titulo' => ['required', 'string', 'max:150'],
            'Resumen_ejecutivo' => ['required', 'string'],
            'reportes' => ['required', 'array', 'min:2'],
            'reportes.*' => [
                'required',
                'integer',
                'distinct',
                'exists:reporte_inteligencia,ID_reporte',
            ],
        ], [
            'Titulo.required' => 'Escribe el título del informe.',
            'Titulo.max' => 'El título no puede superar los 150 caracteres.',
            'Resumen_ejecutivo.required' => 'Escribe el resumen ejecutivo.',
            'reportes.required' => 'Selecciona al menos dos reportes.',
            'reportes.array' => 'La selección de reportes no es válida.',
            'reportes.min' => 'Selecciona al menos dos reportes.',
            'reportes.*.distinct' => 'No puedes seleccionar un reporte repetido.',
            'reportes.*.exists' => 'Uno de los reportes seleccionados ya no existe.',
        ]);

        $informe = DB::transaction(function () use ($datos, $request) {
    $informe = InformeInteligencia::create([
        'Titulo' => $datos['Titulo'],
        'Resumen_ejecutivo' => $datos['Resumen_ejecutivo'],
        'Autor' => $request->user()->Nombre,
        'Fecha_consolidacion' => now(),
    ]);

    $informe->reportes()->sync($datos['reportes']);

    Bitacora::create([
        'ID_usuario' => $request->user()->ID_usuario,
        'Correo_intento' => $request->user()->Correo,
        'Accion' => Bitacora::INFORME_INTELIGENCIA_CREADO,
        'IP' => $request->ip(),
        'Detalle' => 'Informe consolidado #' . $informe->ID_informe,
        'Fecha' => now(),
    ]);

    return $informe;
});

        return redirect()
            ->route('inteligencia.informes.show', $informe->ID_informe)
            ->with('exito', 'Informe consolidado registrado correctamente.');
    }

    public function show(int $id): View
    {
        $informe = InformeInteligencia::query()
            ->with('reportes')
            ->findOrFail($id);

        return view('inteligencia.informes.show', compact('informe'));
    }
}