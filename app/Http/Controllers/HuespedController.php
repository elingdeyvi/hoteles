<?php

namespace App\Http\Controllers;

use App\Models\Huesped;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HuespedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->get('per_page', 20)));
        $query = Huesped::query();

        if ($request->filled('q')) {
            $term = '%'.trim((string) $request->get('q')).'%';
            $query->where(function ($q) use ($term): void {
                $q->where('nombre', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('telefono', 'like', $term)
                    ->orWhere('documento', 'like', $term);
            });
        }

        return response()->json(['data' => $query->orderBy('nombre')->paginate($perPage)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'documento' => ['nullable', 'string', 'max:80'],
            'nacionalidad' => ['nullable', 'string', 'max:80'],
            'direccion' => ['nullable', 'string'],
            'notas' => ['nullable', 'string'],
        ]);

        return response()->json(['data' => Huesped::create($data)], 201);
    }

    public function show(Huesped $huesped): JsonResponse
    {
        return response()->json(['data' => $huesped->load('reservations')]);
    }

    public function update(Request $request, Huesped $huesped): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'documento' => ['nullable', 'string', 'max:80'],
            'nacionalidad' => ['nullable', 'string', 'max:80'],
            'direccion' => ['nullable', 'string'],
            'notas' => ['nullable', 'string'],
        ]);

        $huesped->update($data);

        return response()->json(['data' => $huesped->fresh()]);
    }

    public function destroy(Huesped $huesped): JsonResponse
    {
        $huesped->delete();

        return response()->json(['message' => 'Huésped eliminado.']);
    }
}
