<?php

namespace App\Http\Controllers;

use App\Models\Folio;
use App\Models\PosOutlet;
use App\Services\PosSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(private readonly PosSaleService $pos) {}

    public function catalog(Request $request): JsonResponse
    {
        $outletId = $request->integer('outlet_id');

        $query = PosOutlet::query()
            ->where('is_active', true)
            ->with(['categories' => function ($q): void {
                $q->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with(['products' => fn ($p) => $p->where('is_active', true)->orderBy('name')]);
            }]);

        if ($outletId) {
            $query->where('id', $outletId);
        }

        return response()->json(['data' => $query->orderBy('name')->get()]);
    }

    public function roomsInHouse(): JsonResponse
    {
        return response()->json(['data' => $this->pos->roomsInHouse()]);
    }

    public function chargeToFolio(Request $request): JsonResponse
    {
        $data = $request->validate([
            'folio_id' => ['required', 'exists:folios,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:pos_products,id'],
            'lines.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $folio = Folio::findOrFail($data['folio_id']);
        $result = $this->pos->chargeToFolio($folio, $data['lines'], $request->user()->id);

        return response()->json(['data' => $result], 201);
    }
}
