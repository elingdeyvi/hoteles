<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

class CatalogDelete
{
    /**
     * @param  array<string, bool>  $dependencias
     */
    public static function run(Model $model, array $dependencias, string $exito): RedirectResponse
    {
        $ligados = array_keys(array_filter($dependencias));

        if ($ligados !== []) {
            return back()->with('error', 'No se puede eliminar porque tiene '.implode(', ', $ligados).'.');
        }

        try {
            $model->delete();
        } catch (QueryException) {
            return back()->with('error', 'No se puede eliminar porque hay registros que lo usan.');
        }

        return back()->with('success', $exito);
    }
}
