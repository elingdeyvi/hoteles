<?php

namespace App\Http\Controllers;

use App\Models\PosMovimiento;
use App\Models\PosProduct;
use App\Services\InventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InventarioController extends Controller
{
    public function __construct(private readonly InventarioService $inventario) {}

    public function index(): Response
    {
        $productos = PosProduct::query()
            ->with('category.outlet')
            ->whereHas('category.outlet')
            ->orderBy('name')
            ->get()
            ->map(fn (PosProduct $producto) => [
                'id' => $producto->id,
                'name' => $producto->name,
                'sku' => $producto->sku,
                'price' => $producto->price,
                'costo' => $producto->costo,
                'piezas_caja' => $producto->piezas_caja,
                'ganancia' => round((float) $producto->price - (float) $producto->costo, 2),
                'controla_inventario' => (bool) $producto->controla_inventario,
                'stock_actual' => (float) $producto->stock_actual,
                'stock_minimo' => (float) $producto->stock_minimo,
                'bodega' => (float) $producto->bodega,
                'exhibicion' => (float) $producto->exhibicion,
                'outlet' => $producto->category?->outlet?->name,
                'category' => $producto->category?->name,
                'estado' => $this->estado($producto),
            ]);

        $movimientos = PosMovimiento::query()
            ->with(['producto:id,name', 'usuario:id,name'])
            ->whereHas('producto.category.outlet')
            ->orderByDesc('fecha_movimiento')
            ->limit(40)
            ->get();

        return Inertia::render('Hotel/Inventario/Index', [
            'productos' => $productos,
            'movimientos' => $movimientos,
            'resumen' => [
                'total' => $productos->count(),
                'con_control' => $productos->where('controla_inventario', true)->count(),
                'bajo' => $productos->where('estado', 'bajo')->count(),
                'sin_stock' => $productos->where('estado', 'sin_stock')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pos_product_id' => ['required', 'exists:pos_products,id'],
            'tipo' => ['required', Rule::in([PosMovimiento::ENTRADA, PosMovimiento::SALIDA])],
            'motivo' => ['required', Rule::in(['compra', 'ajuste', 'merma'])],
            'cantidad' => ['required', 'numeric', 'min:0.01'],
            'destino' => ['nullable', Rule::in(['bodega', 'exhibicion'])],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['tipo'] === PosMovimiento::ENTRADA && ! in_array($data['motivo'], ['compra', 'ajuste'], true)) {
            return back()->with('error', 'Una entrada solo puede ser compra o ajuste.');
        }

        if ($data['tipo'] === PosMovimiento::SALIDA && ! in_array($data['motivo'], ['merma', 'ajuste'], true)) {
            return back()->with('error', 'Una salida manual solo puede ser merma o ajuste.');
        }

        $producto = PosProduct::query()->with('category.outlet')->findOrFail($data['pos_product_id']);
        abort_unless($producto->category?->outlet, 404);

        try {
            $this->inventario->registrar(
                $producto,
                $data['tipo'],
                (float) $data['cantidad'],
                $data['motivo'],
                $request->user()->id,
                $data['observaciones'] ?? null,
                null,
                $data['destino'] ?? 'bodega',
            );
        } catch (ValidationException $exception) {
            return back()->with('error', collect($exception->errors())->flatten()->first());
        }

        return back()->with('success', 'Movimiento de inventario registrado.');
    }

    private function estado(PosProduct $producto): string
    {
        if (! $producto->controla_inventario) {
            return 'sin_control';
        }

        $stock = (float) $producto->stock_actual;
        if ($stock <= 0) {
            return 'sin_stock';
        }

        if ($stock <= (float) $producto->stock_minimo) {
            return 'bajo';
        }

        return 'ok';
    }
}
