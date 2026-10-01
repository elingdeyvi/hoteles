<?php

namespace App\Http\Controllers;

use App\Models\PosMovimiento;
use App\Models\PosProduct;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReporteInventarioController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Reportes/MovimientosInventario', [
            'filtros' => [
                'productos' => PosProduct::query()
                    ->whereHas('category.outlet')
                    ->orderBy('name')
                    ->get(['id', 'name', 'sku']),
                'usuarios' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
            ],
        ]);
    }

    public function generales(Request $request): JsonResponse
    {
        $query = $this->base($request)->with(['producto:id,name,sku', 'usuario:id,name']);
        $query->orderBy($request->input('sort_by', 'fecha_movimiento'), $request->input('sort_order', 'desc') === 'asc' ? 'asc' : 'desc');

        return response()->json([
            'data' => $query->paginate((int) $request->input('per_page', 50)),
            'message' => 'Reporte de movimientos generado.',
        ]);
    }

    public function porProducto(Request $request): JsonResponse
    {
        $query = DB::table('pos_movimientos as mi')
            ->join('pos_products as p', 'mi.pos_product_id', '=', 'p.id')
            ->join('pos_categories as c', 'c.id', '=', 'p.pos_category_id')
            ->join('pos_outlets as o', 'o.id', '=', 'c.pos_outlet_id')
            ->select([
                'p.name as producto_nombre',
                'p.sku as producto_codigo',
                DB::raw('SUM(CASE WHEN mi.tipo = "entrada" THEN mi.cantidad ELSE 0 END) as total_entradas'),
                DB::raw('SUM(CASE WHEN mi.tipo = "salida" THEN mi.cantidad ELSE 0 END) as total_salidas'),
                DB::raw('SUM(CASE WHEN mi.tipo = "entrada" THEN mi.cantidad ELSE -mi.cantidad END) as saldo_movimiento'),
                DB::raw('COUNT(*) as total_movimientos'),
            ])
            ->groupBy('p.id', 'p.name', 'p.sku');

        $this->filtrarTabla($query, $request, 'mi');
        $rows = $query->orderByDesc('total_movimientos')->get();

        return response()->json(['data' => $rows]);
    }

    public function porTipo(Request $request): JsonResponse
    {
        $query = DB::table('pos_movimientos as mi')
            ->join('pos_products as p', 'mi.pos_product_id', '=', 'p.id')
            ->join('pos_categories as c', 'c.id', '=', 'p.pos_category_id')
            ->join('pos_outlets as o', 'o.id', '=', 'c.pos_outlet_id')
            ->select([
                'mi.tipo as tipo_movimiento',
                'mi.motivo as tipo_nombre',
                DB::raw('SUM(mi.cantidad) as total_cantidad'),
                DB::raw('COUNT(*) as total_movimientos'),
            ])
            ->groupBy('mi.tipo', 'mi.motivo');

        $this->filtrarTabla($query, $request, 'mi');

        return response()->json(['data' => $query->orderByDesc('total_cantidad')->get()]);
    }

    public function porUsuario(Request $request): JsonResponse
    {
        $query = DB::table('pos_movimientos as mi')
            ->join('pos_products as p', 'mi.pos_product_id', '=', 'p.id')
            ->join('pos_categories as c', 'c.id', '=', 'p.pos_category_id')
            ->join('pos_outlets as o', 'o.id', '=', 'c.pos_outlet_id')
            ->leftJoin('users as u', 'u.id', '=', 'mi.user_id')
            ->select([
                DB::raw("COALESCE(u.name, 'Sin usuario') as usuario_nombre"),
                DB::raw('SUM(CASE WHEN mi.tipo = "entrada" THEN mi.cantidad ELSE 0 END) as total_entradas'),
                DB::raw('SUM(CASE WHEN mi.tipo = "salida" THEN mi.cantidad ELSE 0 END) as total_salidas'),
                DB::raw('COUNT(*) as total_movimientos'),
            ])
            ->groupBy('u.id', 'u.name');

        $this->filtrarTabla($query, $request, 'mi');

        return response()->json(['data' => $query->orderByDesc('total_movimientos')->get()]);
    }

    public function bajoStock(): JsonResponse
    {
        $rows = PosProduct::query()
            ->whereHas('category.outlet')
            ->where('controla_inventario', true)
            ->whereColumn('stock_actual', '<=', 'stock_minimo')
            ->orderBy('stock_actual')
            ->get(['sku', 'name', 'stock_actual', 'stock_minimo'])
            ->map(fn (PosProduct $producto) => [
                'codigo' => $producto->sku,
                'nombre' => $producto->name,
                'stock_actual' => $producto->stock_actual,
                'stock_minimo' => $producto->stock_minimo,
            ]);

        return response()->json(['data' => $rows]);
    }

    public function resumenGeneral(Request $request): JsonResponse
    {
        $productos = PosProduct::query()->whereHas('category.outlet')->where('controla_inventario', true);
        $movimientos = $this->base($request);

        $valor = (float) PosProduct::query()
            ->whereHas('category.outlet')
            ->where('controla_inventario', true)
            ->selectRaw('COALESCE(SUM(stock_actual * costo), 0) as valor')
            ->value('valor');

        return response()->json([
            'data' => [
                'productos' => [
                    'total' => (clone $productos)->count(),
                    'bajo_stock' => (clone $productos)->whereColumn('stock_actual', '<=', 'stock_minimo')->count(),
                ],
                'movimientos' => [
                    'total' => (clone $movimientos)->count(),
                    'entradas' => (float) (clone $movimientos)->where('tipo', PosMovimiento::ENTRADA)->sum('cantidad'),
                    'salidas' => (float) (clone $movimientos)->where('tipo', PosMovimiento::SALIDA)->sum('cantidad'),
                ],
                'inventario' => [
                    'valor_total' => round($valor, 2),
                ],
            ],
        ]);
    }

    private function base(Request $request)
    {
        $query = PosMovimiento::query()->whereHas('producto.category.outlet');

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_movimiento', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_movimiento', '<=', $request->input('fecha_hasta'));
        }
        if ($request->filled('tipo_movimiento')) {
            $query->where('tipo', $request->input('tipo_movimiento'));
        }
        if ($request->filled('producto_id')) {
            $query->where('pos_product_id', $request->integer('producto_id'));
        }
        if ($request->filled('usuario_id')) {
            $query->where('user_id', $request->integer('usuario_id'));
        }

        return $query;
    }

    private function filtrarTabla($query, Request $request, string $alias): void
    {
        $propertyId = \App\Support\CurrentProperty::id();
        if ($propertyId) {
            $query->where('o.property_id', $propertyId);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate($alias.'.fecha_movimiento', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate($alias.'.fecha_movimiento', '<=', $request->input('fecha_hasta'));
        }
        if ($request->filled('tipo_movimiento')) {
            $query->where($alias.'.tipo', $request->input('tipo_movimiento'));
        }
        if ($request->filled('producto_id')) {
            $query->where($alias.'.pos_product_id', $request->integer('producto_id'));
        }
        if ($request->filled('usuario_id')) {
            $query->where($alias.'.user_id', $request->integer('usuario_id'));
        }
    }
}
