<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UbicacionRequest extends FormRequest
{
    // tipo => [tabla, llave primaria] (según el script de la BD)
    private const ENTIDADES = [
        'tripulacion'  => ['tripulaciones', 'ID_tripulacion'],
        'personaje'    => ['personajes', 'ID_personaje'],
        'barco'        => ['barcos', 'ID_barco'],
        'organizacion' => ['organizaciones', 'ID_organizacion'], // incluye la Marina
    ];

    // Límites del mapa (propuesta: confirmar con el equipo)
    private const X_MIN = 0;
    private const X_MAX = 1500;
    private const Y_MIN = 0;
    private const Y_MAX = 1000;

    public function authorize(): bool
    {
        // Los permisos los revisa el middleware 'permiso:...' en la ruta.
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tipo' => $this->route('tipo'),
            'id'   => $this->route('id'),
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'tipo' => ['required', Rule::in(array_keys(self::ENTIDADES))],
            'id'   => ['required', 'integer'],
            'x'    => ['required', 'numeric', 'between:' . self::X_MIN . ',' . self::X_MAX],
            'y'    => ['required', 'numeric', 'between:' . self::Y_MIN . ',' . self::Y_MAX],
        ];

        $tipo = $this->route('tipo');

        if (isset(self::ENTIDADES[$tipo])) {
            [$tabla, $pk] = self::ENTIDADES[$tipo];
            $rules['id'][] = "exists:{$tabla},{$pk}";
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'tipo.in'    => 'El tipo de entidad no es válido.',
            'x.required' => 'Falta la coordenada X.',
            'y.required' => 'Falta la coordenada Y.',
            'x.numeric'  => 'La coordenada X debe ser un número.',
            'y.numeric'  => 'La coordenada Y debe ser un número.',
            'x.between'  => 'La coordenada X está fuera del mapa.',
            'y.between'  => 'La coordenada Y está fuera del mapa.',
            'id.exists'  => 'La entidad indicada no existe.',
        ];
    }
}