<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Huesped;
use App\Models\PosProduct;
use App\Models\User;
use App\Support\CurrentProperty;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteVentaController extends Controller
{
    public const TIPOS = [
        'por_dia' => [
            'titulo' => 'Ventas por día',
            'descripcion' => 'Cobros de folio agrupados por día.',
            'icono' => 'fa-calendar-day',
        ],
        'por_mes' => [
            'titulo' => 'Ventas por mes',
            'descripcion' => 'Comparativo mensual de cobros.',
            'icono' => 'fa-calendar',
        ],
        'por_cliente' => [
            'titulo' => 'Ventas por huésped',
            'descripcion' => 'Ranking de huéspedes por importe cobrado.',
            'icono' => 'fa-user-group',
        ],
        'por_producto' => [
            'titulo' => 'Ventas por producto',
            'descripcion' => 'Productos del POS más cargados a folio.',
            'icono' => 'fa-box',
        ],
        'por_vendedor' => [
            'titulo' => 'Ventas por usuario',
            'descripcion' => 'Cobros registrados por cada usuario.',
            'icono' => 'fa-user-tie',
        ],
        'por_metodo_pago' => [
            'titulo' => 'Ventas por método de pago',
            'descripcion' => 'Efectivo, tarjeta, transferencia y otros.',
            'icono' => 'fa-credit-card',
        ],
        'por_tipo' => [
            'titulo' => 'Ventas por tipo de cargo',
            'descripcion' => 'Habitación, persona extra y productos.',
            'icono' => 'fa-tags',
        ],
        'por_caja' => [
            'titulo' => 'Ventas por caja',
            'descripcion' => 'Cobros ligados a cada caja.',
            'icono' => 'fa-cash-register',
        ],
        'detalle' => [
            'titulo' => 'Detalle de cobros',
            'descripcion' => 'Listado de pagos del periodo.',
            'icono' => 'fa-receipt',
        ],
        'por_utilidad' => [
            'titulo' => 'Utilidad y ganancia',
            'descripcion' => 'Venta, costo y ganancia por producto, en folio y venta al público.',
            'icono' => 'fa-sack-dollar',
        ],
    ];

    public function index(): Response
    {
        return Inertia::render('Reportes/Ventas/Index', [
            'reportes' => collect(self::TIPOS)->map(fn ($meta, $key) => [
                'key' => $key,
                ...$meta,
                'url' => route('reportes.ventas.show', $key),
            ])->values()->all(),
        ]);
    }

    public function show(Request $request, string $tipo): Response
    {
        abort_unless(array_key_exists($tipo, self::TIPOS), 404);

        $filtros = $this->filtros($request);
        $resultado = $this->generar($tipo, $filtros);

        return Inertia::render('Reportes/Ventas/Show', [
            'tipo' => $tipo,
            'meta' => self::TIPOS[$tipo],
            'filtros' => $filtros,
            'cajas' => Caja::query()->orderBy('nombre')->get(['id', 'nombre', 'codigo']),
            'huespedes' => Huesped::query()->orderBy('nombre')->limit(300)->get(['id', 'nombre']),
            'vendedores' => User::query()->orderBy('name')->get(['id', 'name']),
            'metodosPago' => ['efectivo', 'tarjeta', 'transferencia', 'otro'],
            'productos' => PosProduct::query()
                ->whereHas('category.outlet')
                ->orderBy('name')
                ->get(['id', 'name', 'sku']),
            'resultado' => $resultado,
            'exportUrl' => route('reportes.ventas.export', array_merge(['tipo' => $tipo], $this->filtrosQuery($filtros))),
        ]);
    }

    public function export(Request $request, string $tipo): StreamedResponse
    {
        abort_unless(array_key_exists($tipo, self::TIPOS), 404);

        $filtros = $this->filtros($request);
        $resultado = $this->generar($tipo, $filtros);
        $filename = 'reporte_'.$tipo.'_'.$filtros['fecha_inicio'].'_'.$filtros['fecha_fin'].'.csv';

        return response()->streamDownload(function () use ($resultado) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, array_column($resultado['columns'], 'label'));
            foreach ($resultado['rows'] as $row) {
                $line = [];
                foreach ($resultado['columns'] as $col) {
                    $line[] = $row[$col['key']] ?? '';
                }
                fputcsv($out, $line);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtros(Request $request): array
    {
        $inicio = Carbon::parse($request->input('fecha_inicio', now()->subDays(29)->toDateString()))->startOfDay();
        $fin = Carbon::parse($request->input('fecha_fin', now()->toDateString()))->endOfDay();
        if ($inicio->gt($fin)) {
            [$inicio, $fin] = [$fin->copy()->startOfDay(), $inicio->copy()->endOfDay()];
        }

        return [
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin' => $fin->toDateString(),
            'tipo_cargo' => (string) $request->input('tipo_cargo', ''),
            'caja_id' => $request->filled('caja_id') ? (int) $request->input('caja_id') : '',
            'huesped_id' => $request->filled('huesped_id') ? (int) $request->input('huesped_id') : '',
            'user_id' => $request->filled('user_id') ? (int) $request->input('user_id') : '',
            'producto_id' => $request->filled('producto_id') ? (int) $request->input('producto_id') : '',
            'metodo_pago' => (string) $request->input('metodo_pago', ''),
        ];
    }

    private function filtrosQuery(array $filtros): array
    {
        return array_filter($filtros, fn ($valor) => $valor !== null && $valor !== '');
    }

    private function rango(array $filtros): array
    {
        return [
            Carbon::parse($filtros['fecha_inicio'])->startOfDay(),
            Carbon::parse($filtros['fecha_fin'])->endOfDay(),
        ];
    }

    private function pagos(array $filtros)
    {
        [$inicio, $fin] = $this->rango($filtros);
        $q = DB::table('folio_payments')
            ->join('folios', 'folios.id', '=', 'folio_payments.folio_id')
            ->join('stays', 'stays.id', '=', 'folios.stay_id')
            ->join('rooms', 'rooms.id', '=', 'stays.room_id')
            ->join('reservations', 'reservations.id', '=', 'stays.reservation_id')
            ->join('huespedes', 'huespedes.id', '=', 'reservations.huesped_id')
            ->whereBetween('folio_payments.created_at', [$inicio, $fin]);

        if ($propertyId = CurrentProperty::id()) {
            $q->where('rooms.property_id', $propertyId);
        }
        if (($filtros['huesped_id'] ?? '') !== '') {
            $q->where('huespedes.id', $filtros['huesped_id']);
        }
        if (($filtros['user_id'] ?? '') !== '') {
            $q->where('folio_payments.received_by', $filtros['user_id']);
        }
        if (($filtros['metodo_pago'] ?? '') !== '') {
            $q->where('folio_payments.payment_method', $filtros['metodo_pago']);
        }
        if (($filtros['caja_id'] ?? '') !== '') {
            $q->whereExists(function ($sub) use ($filtros) {
                $sub->selectRaw('1')
                    ->from('aperturas_caja')
                    ->whereColumn('aperturas_caja.id', 'folio_payments.apertura_caja_id')
                    ->where('aperturas_caja.caja_id', $filtros['caja_id']);
            });
        }
        if (($filtros['producto_id'] ?? '') !== '') {
            $q->whereExists(function ($sub) use ($filtros) {
                $sub->selectRaw('1')
                    ->from('folio_charges')
                    ->whereColumn('folio_charges.folio_id', 'folios.id')
                    ->where('folio_charges.pos_product_id', $filtros['producto_id']);
            });
        }
        if (($filtros['tipo_cargo'] ?? '') !== '') {
            $q->whereExists(function ($sub) use ($filtros) {
                $sub->selectRaw('1')
                    ->from('folio_charges')
                    ->whereColumn('folio_charges.folio_id', 'folios.id')
                    ->where('folio_charges.charge_type', $filtros['tipo_cargo']);
            });
        }

        return $q;
    }

    private function cargos(array $filtros)
    {
        [$inicio, $fin] = $this->rango($filtros);
        $q = DB::table('folio_charges')
            ->join('folios', 'folios.id', '=', 'folio_charges.folio_id')
            ->join('stays', 'stays.id', '=', 'folios.stay_id')
            ->join('rooms', 'rooms.id', '=', 'stays.room_id')
            ->join('reservations', 'reservations.id', '=', 'stays.reservation_id')
            ->whereBetween('folio_charges.created_at', [$inicio, $fin]);

        if ($propertyId = CurrentProperty::id()) {
            $q->where('rooms.property_id', $propertyId);
        }
        if (($filtros['huesped_id'] ?? '') !== '') {
            $q->where('reservations.huesped_id', $filtros['huesped_id']);
        }
        if (($filtros['user_id'] ?? '') !== '') {
            $q->where('folio_charges.charged_by', $filtros['user_id']);
        }
        if (($filtros['producto_id'] ?? '') !== '') {
            $q->where('folio_charges.pos_product_id', $filtros['producto_id']);
        }
        if (($filtros['tipo_cargo'] ?? '') !== '') {
            $q->where('folio_charges.charge_type', $filtros['tipo_cargo']);
        }
        if (($filtros['caja_id'] ?? '') !== '') {
            $q->whereExists(function ($sub) use ($filtros) {
                $sub->selectRaw('1')
                    ->from('aperturas_caja')
                    ->whereColumn('aperturas_caja.id', 'folio_charges.apertura_caja_id')
                    ->where('aperturas_caja.caja_id', $filtros['caja_id']);
            });
        }

        return $q;
    }

    private function generar(string $tipo, array $filtros): array
    {
        return match ($tipo) {
            'por_dia' => $this->porDia($filtros),
            'por_mes' => $this->porMes($filtros),
            'por_cliente' => $this->porHuesped($filtros),
            'por_producto' => $this->porProducto($filtros),
            'por_vendedor' => $this->porUsuario($filtros),
            'por_metodo_pago' => $this->porMetodo($filtros),
            'por_tipo' => $this->porTipo($filtros),
            'por_caja' => $this->porCaja($filtros),
            'detalle' => $this->detalle($filtros),
            'por_utilidad' => $this->porUtilidad($filtros),
            default => abort(404),
        };
    }

    private function resumenDe(float $total, int $cantidad): array
    {
        return [
            'total' => round($total, 2),
            'cantidad' => $cantidad,
            'ticket_promedio' => $cantidad > 0 ? round($total / $cantidad, 2) : 0,
        ];
    }

    private function porDia(array $filtros): array
    {
        $rowsRaw = $this->pagos($filtros)
            ->selectRaw('DATE(folio_payments.created_at) as periodo, COUNT(*) as cantidad, COALESCE(SUM(folio_payments.amount),0) as total')
            ->groupBy(DB::raw('DATE(folio_payments.created_at)'))
            ->orderBy(DB::raw('DATE(folio_payments.created_at)'))
            ->get();

        $rows = $rowsRaw->map(fn ($r) => [
            'periodo' => $r->periodo,
            'cantidad' => (int) $r->cantidad,
            'total' => round((float) $r->total, 2),
            'ticket_promedio' => $r->cantidad > 0 ? round((float) $r->total / (int) $r->cantidad, 2) : 0,
        ])->all();

        return [
            'resumen' => $this->resumenDe((float) $rowsRaw->sum('total'), (int) $rowsRaw->sum('cantidad')),
            'chart' => [
                'labels' => array_map(fn ($r) => Carbon::parse($r['periodo'])->format('d/m'), $rows),
                'totales' => array_column($rows, 'total'),
                'cantidades' => array_column($rows, 'cantidad'),
            ],
            'columns' => [
                ['key' => 'periodo', 'label' => 'Fecha'],
                ['key' => 'cantidad', 'label' => 'Cobros'],
                ['key' => 'total', 'label' => 'Total'],
                ['key' => 'ticket_promedio', 'label' => 'Ticket promedio'],
            ],
            'rows' => $rows,
        ];
    }

    private function porMes(array $filtros): array
    {
        $rowsRaw = $this->pagos($filtros)
            ->selectRaw("DATE_FORMAT(folio_payments.created_at, '%Y-%m') as periodo, COUNT(*) as cantidad, COALESCE(SUM(folio_payments.amount),0) as total")
            ->groupBy(DB::raw("DATE_FORMAT(folio_payments.created_at, '%Y-%m')"))
            ->orderBy(DB::raw("DATE_FORMAT(folio_payments.created_at, '%Y-%m')"))
            ->get();

        $rows = $rowsRaw->map(fn ($r) => [
            'periodo_label' => Carbon::createFromFormat('Y-m', $r->periodo)->translatedFormat('M Y'),
            'cantidad' => (int) $r->cantidad,
            'total' => round((float) $r->total, 2),
            'ticket_promedio' => $r->cantidad > 0 ? round((float) $r->total / (int) $r->cantidad, 2) : 0,
        ])->all();

        return [
            'resumen' => $this->resumenDe((float) $rowsRaw->sum('total'), (int) $rowsRaw->sum('cantidad')),
            'chart' => [
                'labels' => array_column($rows, 'periodo_label'),
                'totales' => array_column($rows, 'total'),
                'cantidades' => array_column($rows, 'cantidad'),
            ],
            'columns' => [
                ['key' => 'periodo_label', 'label' => 'Mes'],
                ['key' => 'cantidad', 'label' => 'Cobros'],
                ['key' => 'total', 'label' => 'Total'],
                ['key' => 'ticket_promedio', 'label' => 'Ticket promedio'],
            ],
            'rows' => $rows,
        ];
    }

    private function porHuesped(array $filtros): array
    {
        $rowsRaw = $this->pagos($filtros)
            ->selectRaw('huespedes.nombre as cliente, COUNT(folio_payments.id) as cantidad, COALESCE(SUM(folio_payments.amount),0) as total')
            ->groupBy('huespedes.id', 'huespedes.nombre')
            ->orderByDesc(DB::raw('COALESCE(SUM(folio_payments.amount),0)'))
            ->limit(200)
            ->get();

        $rows = $rowsRaw->map(fn ($r) => [
            'cliente' => $r->cliente,
            'cantidad' => (int) $r->cantidad,
            'total' => round((float) $r->total, 2),
            'ticket_promedio' => $r->cantidad > 0 ? round((float) $r->total / (int) $r->cantidad, 2) : 0,
        ])->all();

        return $this->paquete($rows, [
            ['key' => 'cliente', 'label' => 'Huésped'],
            ['key' => 'cantidad', 'label' => 'Cobros'],
            ['key' => 'total', 'label' => 'Total'],
            ['key' => 'ticket_promedio', 'label' => 'Ticket promedio'],
        ], 'cliente');
    }

    private function porProducto(array $filtros): array
    {
        $rowsRaw = $this->cargos($filtros)
            ->join('pos_products', 'pos_products.id', '=', 'folio_charges.pos_product_id')
            ->selectRaw('pos_products.sku as codigo, pos_products.name as producto, SUM(folio_charges.quantity) as cantidad, COALESCE(SUM(folio_charges.amount),0) as total')
            ->groupBy('pos_products.id', 'pos_products.sku', 'pos_products.name')
            ->orderByDesc('total')
            ->limit(200)
            ->get();

        $rows = $rowsRaw->map(fn ($r) => [
            'codigo' => $r->codigo,
            'producto' => $r->producto,
            'cantidad' => round((float) $r->cantidad, 2),
            'total' => round((float) $r->total, 2),
        ])->all();

        return $this->paquete($rows, [
            ['key' => 'codigo', 'label' => 'Código'],
            ['key' => 'producto', 'label' => 'Producto'],
            ['key' => 'cantidad', 'label' => 'Cantidad'],
            ['key' => 'total', 'label' => 'Total'],
        ], 'producto', count($rows));
    }

    private function porUsuario(array $filtros): array
    {
        $rowsRaw = $this->pagos($filtros)
            ->leftJoin('users', 'users.id', '=', 'folio_payments.received_by')
            ->selectRaw("COALESCE(users.name, 'Sin usuario') as vendedor, COUNT(folio_payments.id) as cantidad, COALESCE(SUM(folio_payments.amount),0) as total")
            ->groupBy('users.id', 'users.name')
            ->orderByDesc(DB::raw('COALESCE(SUM(folio_payments.amount),0)'))
            ->get();

        $rows = $rowsRaw->map(fn ($r) => [
            'vendedor' => $r->vendedor,
            'cantidad' => (int) $r->cantidad,
            'total' => round((float) $r->total, 2),
            'ticket_promedio' => $r->cantidad > 0 ? round((float) $r->total / (int) $r->cantidad, 2) : 0,
        ])->all();

        return $this->paquete($rows, [
            ['key' => 'vendedor', 'label' => 'Usuario'],
            ['key' => 'cantidad', 'label' => 'Cobros'],
            ['key' => 'total', 'label' => 'Total'],
            ['key' => 'ticket_promedio', 'label' => 'Ticket promedio'],
        ], 'vendedor');
    }

    private function porMetodo(array $filtros): array
    {
        $rowsRaw = $this->pagos($filtros)
            ->selectRaw('folio_payments.payment_method as metodo, COUNT(*) as cantidad, COALESCE(SUM(folio_payments.amount),0) as total')
            ->groupBy('folio_payments.payment_method')
            ->orderByDesc('total')
            ->get();

        $rows = $rowsRaw->map(fn ($r) => [
            'metodo' => $this->etiquetaMetodo($r->metodo),
            'cantidad' => (int) $r->cantidad,
            'total' => round((float) $r->total, 2),
        ])->all();

        return $this->paquete($rows, [
            ['key' => 'metodo', 'label' => 'Método'],
            ['key' => 'cantidad', 'label' => 'Cobros'],
            ['key' => 'total', 'label' => 'Total'],
        ], 'metodo');
    }

    private function porTipo(array $filtros): array
    {
        $rowsRaw = $this->cargos($filtros)
            ->selectRaw('folio_charges.charge_type as tipo, COUNT(*) as cantidad, COALESCE(SUM(folio_charges.amount),0) as total')
            ->groupBy('folio_charges.charge_type')
            ->orderByDesc('total')
            ->get();

        $rows = $rowsRaw->map(fn ($r) => [
            'tipo' => $this->etiquetaTipo($r->tipo),
            'cantidad' => (int) $r->cantidad,
            'total' => round((float) $r->total, 2),
        ])->all();

        return $this->paquete($rows, [
            ['key' => 'tipo', 'label' => 'Tipo'],
            ['key' => 'cantidad', 'label' => 'Cargos'],
            ['key' => 'total', 'label' => 'Total'],
        ], 'tipo');
    }

    private function porCaja(array $filtros): array
    {
        $rowsRaw = $this->pagos($filtros)
            ->leftJoin('aperturas_caja', 'aperturas_caja.id', '=', 'folio_payments.apertura_caja_id')
            ->leftJoin('cajas', 'cajas.id', '=', 'aperturas_caja.caja_id')
            ->selectRaw("COALESCE(cajas.nombre, 'Sin caja') as caja, COUNT(folio_payments.id) as cantidad, COALESCE(SUM(folio_payments.amount),0) as total")
            ->groupBy('cajas.id', 'cajas.nombre')
            ->orderByDesc(DB::raw('COALESCE(SUM(folio_payments.amount),0)'))
            ->get();

        $rows = $rowsRaw->map(fn ($r) => [
            'caja' => $r->caja,
            'cantidad' => (int) $r->cantidad,
            'total' => round((float) $r->total, 2),
        ])->all();

        return $this->paquete($rows, [
            ['key' => 'caja', 'label' => 'Caja'],
            ['key' => 'cantidad', 'label' => 'Cobros'],
            ['key' => 'total', 'label' => 'Total'],
        ], 'caja');
    }

    private function detalle(array $filtros): array
    {
        $rowsRaw = $this->pagos($filtros)
            ->leftJoin('users', 'users.id', '=', 'folio_payments.received_by')
            ->select([
                'folio_payments.created_at',
                'folios.folio_number',
                'huespedes.nombre as huesped',
                'folio_payments.payment_method',
                'folio_payments.reference',
                'folio_payments.amount',
                'users.name as usuario',
            ])
            ->orderByDesc('folio_payments.created_at')
            ->limit(500)
            ->get();

        $rows = $rowsRaw->map(fn ($r) => [
            'fecha' => Carbon::parse($r->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'folio' => $r->folio_number,
            'huesped' => $r->huesped,
            'metodo' => $this->etiquetaMetodo($r->payment_method),
            'referencia' => $r->reference,
            'total' => round((float) $r->amount, 2),
            'usuario' => $r->usuario,
        ])->all();

        $total = (float) $rowsRaw->sum('amount');

        return [
            'resumen' => $this->resumenDe($total, count($rows)),
            'chart' => ['labels' => [], 'totales' => [], 'cantidades' => []],
            'columns' => [
                ['key' => 'fecha', 'label' => 'Fecha'],
                ['key' => 'folio', 'label' => 'Folio'],
                ['key' => 'huesped', 'label' => 'Huésped'],
                ['key' => 'metodo', 'label' => 'Método'],
                ['key' => 'referencia', 'label' => 'Referencia'],
                ['key' => 'total', 'label' => 'Importe'],
                ['key' => 'usuario', 'label' => 'Usuario'],
            ],
            'rows' => $rows,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array{key: string, label: string}>  $columns
     */
    private function porUtilidad(array $filtros): array
    {
        [$inicio, $fin] = $this->rango($filtros);
        $propertyId = CurrentProperty::id();
        $mapa = [];

        $acumular = function (int $id, ?string $codigo, string $producto, float $cantidad, float $venta, float $costo) use (&$mapa): void {
            if (! isset($mapa[$id])) {
                $mapa[$id] = [
                    'codigo' => $codigo,
                    'producto' => $producto,
                    'cantidad' => 0.0,
                    'venta' => 0.0,
                    'costo' => 0.0,
                ];
            }
            $mapa[$id]['cantidad'] += $cantidad;
            $mapa[$id]['venta'] += $venta;
            $mapa[$id]['costo'] += $costo;
        };

        $folio = DB::table('folio_charges')
            ->join('pos_products', 'pos_products.id', '=', 'folio_charges.pos_product_id')
            ->join('folios', 'folios.id', '=', 'folio_charges.folio_id')
            ->join('stays', 'stays.id', '=', 'folios.stay_id')
            ->join('rooms', 'rooms.id', '=', 'stays.room_id')
            ->where('folio_charges.charge_type', 'pos')
            ->whereBetween('folio_charges.created_at', [$inicio, $fin]);
        if ($propertyId) {
            $folio->where('rooms.property_id', $propertyId);
        }
        if (($filtros['producto_id'] ?? '') !== '') {
            $folio->where('pos_products.id', $filtros['producto_id']);
        }
        foreach ($folio->selectRaw('pos_products.id as producto_id, pos_products.sku as codigo, pos_products.name as producto, SUM(folio_charges.quantity) as cantidad, SUM(folio_charges.amount * folio_charges.quantity) as venta, SUM(pos_products.costo * folio_charges.quantity) as costo')->groupBy('pos_products.id', 'pos_products.sku', 'pos_products.name')->get() as $fila) {
            $acumular((int) $fila->producto_id, $fila->codigo, (string) $fila->producto, (float) $fila->cantidad, (float) $fila->venta, (float) $fila->costo);
        }

        $publico = DB::table('pos_venta_lineas')
            ->join('pos_ventas', 'pos_ventas.id', '=', 'pos_venta_lineas.pos_venta_id')
            ->whereBetween('pos_ventas.created_at', [$inicio, $fin]);
        if ($propertyId) {
            $publico->where('pos_ventas.property_id', $propertyId);
        }
        if (($filtros['producto_id'] ?? '') !== '') {
            $publico->where('pos_venta_lineas.pos_product_id', $filtros['producto_id']);
        }
        foreach ($publico->selectRaw('pos_venta_lineas.pos_product_id as producto_id, MAX(pos_venta_lineas.nombre) as producto, SUM(pos_venta_lineas.cantidad) as cantidad, SUM(pos_venta_lineas.importe) as venta, SUM(pos_venta_lineas.costo * pos_venta_lineas.cantidad) as costo')->groupBy('pos_venta_lineas.pos_product_id')->get() as $fila) {
            $acumular((int) ($fila->producto_id ?: 0), null, (string) $fila->producto, (float) $fila->cantidad, (float) $fila->venta, (float) $fila->costo);
        }

        $rows = collect($mapa)->map(function (array $fila) {
            $venta = round($fila['venta'], 2);
            $costo = round($fila['costo'], 2);
            $utilidad = round($venta - $costo, 2);

            return [
                'codigo' => $fila['codigo'],
                'producto' => $fila['producto'],
                'cantidad' => round($fila['cantidad'], 2),
                'venta' => $venta,
                'costo' => $costo,
                'utilidad' => $utilidad,
                'total' => $utilidad,
                'margen' => $venta > 0 ? round(($utilidad / $venta) * 100, 1).'%' : '0%',
            ];
        })->sortByDesc('utilidad')->values()->all();

        $paquete = $this->paquete($rows, [
            ['key' => 'codigo', 'label' => 'Código'],
            ['key' => 'producto', 'label' => 'Producto'],
            ['key' => 'cantidad', 'label' => 'Cantidad'],
            ['key' => 'venta', 'label' => 'Venta'],
            ['key' => 'costo', 'label' => 'Costo'],
            ['key' => 'utilidad', 'label' => 'Utilidad'],
            ['key' => 'margen', 'label' => 'Margen'],
        ], 'producto');

        $venta = round((float) array_sum(array_column($rows, 'venta')), 2);
        $utilidad = round((float) array_sum(array_column($rows, 'utilidad')), 2);
        $paquete['resumen']['total'] = $utilidad;
        $paquete['resumen']['ticket_promedio'] = $venta > 0 ? round(($utilidad / $venta) * 100, 1) : 0;
        $paquete['resumen']['venta'] = $venta;

        return $paquete;
    }

    private function paquete(array $rows, array $columns, string $labelKey, ?int $cantidad = null): array
    {
        $top = array_slice($rows, 0, 12);

        return [
            'resumen' => $this->resumenDe(
                (float) array_sum(array_column($rows, 'total')),
                $cantidad ?? (int) array_sum(array_column($rows, 'cantidad'))
            ),
            'chart' => [
                'labels' => array_column($top, $labelKey),
                'totales' => array_column($top, 'total'),
                'cantidades' => array_column($top, 'cantidad'),
            ],
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    private function etiquetaMetodo(?string $metodo): string
    {
        return match ($metodo) {
            'efectivo' => 'Efectivo',
            'tarjeta' => 'Tarjeta',
            'transferencia' => 'Transferencia',
            'otro' => 'Otro',
            default => $metodo ?: '—',
        };
    }

    private function etiquetaTipo(?string $tipo): string
    {
        return match ($tipo) {
            'habitacion' => 'Habitación',
            'extra' => 'Extra',
            'pos' => 'Producto',
            default => $tipo ?: '—',
        };
    }
}
