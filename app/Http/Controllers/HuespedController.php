<?php

namespace App\Http\Controllers;

use App\Models\Huesped;
use App\Support\CatalogDelete;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HuespedController extends Controller
{

    public function index(Request $request): Response
    {
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

        return Inertia::render('Hotel/Huespedes/Index', [
            'huespedes' => $query->orderBy('nombre')->paginate(20)->withQueryString(),
            'filters' => $request->only(['q']),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $this->validated($request);
        $this->guardarIdentificacion($request, $data);
        $huesped = Huesped::create($data);

        // Inertia también manda X-Requested-With; el JSON es solo para el alta rápida (axios).
        if (! $request->header('X-Inertia') && ($request->expectsJson() || $request->wantsJson())) {
            return response()->json([
                'data' => $huesped->only(['id', 'nombre', 'email', 'telefono', 'documento']),
                'message' => 'Huésped registrado.',
            ], JsonResponse::HTTP_CREATED);
        }

        return back()->with('success', 'Huésped registrado.');
    }

    public function update(Request $request, Huesped $huesped): RedirectResponse
    {
        $data = $this->validated($request);
        $this->guardarIdentificacion($request, $data);
        $huesped->update($data);

        return back()->with('success', 'Huésped actualizado.');
    }

    public function destroy(Huesped $huesped): RedirectResponse
    {
        return CatalogDelete::run($huesped, [
            'reservaciones' => $huesped->reservations()->exists(),
        ], 'Huésped eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'documento' => ['nullable', 'string', 'max:80'],
            'identificacion' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'nacionalidad' => ['nullable', 'string', 'max:80'],
            'direccion' => ['nullable', 'string'],
            'notas' => ['nullable', 'string'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function guardarIdentificacion(Request $request, array &$data): void
    {
        if (! $request->hasFile('identificacion')) {
            return;
        }

        $data['identificacion_path'] = $request->file('identificacion')->store('identificaciones', 'public');
    }
}
