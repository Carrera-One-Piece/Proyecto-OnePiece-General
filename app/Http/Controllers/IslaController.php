<?php
namespace App\Http\Controllers;
use App\Models\Isla;

class IslaController extends Controller {
    public function index() {
        return Isla::select('ID_isla', 'Nombre', 'Coordenada_X', 'Coordenada_Y')
            ->get()
            ->map(fn($i) => [
                'id'     => $i->ID_isla,
                'nombre' => $i->Nombre,
                'coords' => [(float) $i->Coordenada_X, (float) $i->Coordenada_Y],
            ]);
    }
}
