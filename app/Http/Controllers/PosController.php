<?php

namespace App\Http\Controllers;

use App\Models\Folio;
use App\Models\FolioCharge;
use App\Models\PosCategory;
use App\Models\PosOutlet;
use App\Models\PosProduct;
use App\Services\PosSaleService;
use App\Support\CatalogDelete;
use App\Support\CurrentProperty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function __construct(private readonly PosSaleService $pos) {}

    public function index(): Response
    {
        $outlets = PosOutlet::query()
            ->where('is_active', true)
            ->with(['categories' => function ($q): void {
                $q->where('is_active', true)
                    ->orderBy('sort_order')
                    ->with(['products' => fn ($p) => $p->where('is_active', true)->orderBy('name')]);
            }])
            ->orderBy('name')
            ->get();

        return Inertia::render('Hotel/Pos/Index', [
            'outlets' => $outlets,
            'roomsInHouse' => $this->pos->roomsInHouse(),
        ]);
    }

    public function chargeToFolio(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'folio_id' => ['required', 'exists:folios,id'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:pos_products,id'],
            'lines.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $folio = Folio::findOrFail($data['folio_id']);
        $this->pos->chargeToFolio($folio, $data['lines'], $request->user()->id);

        return back()->with('success', 'Consumo cargado al folio.');
    }

    public function vender(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'destino' => ['required', 'in:publico,habitacion'],
            'folio_id' => ['required_if:destino,habitacion', 'nullable', 'exists:folios,id'],
            'payment_method' => ['required_if:destino,publico', 'nullable', 'string', 'max:30'],
            'recibido' => ['nullable', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:pos_products,id'],
            'lines.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        if ($data['destino'] === 'habitacion') {
            $folio = Folio::findOrFail($data['folio_id']);
            $this->pos->chargeToFolio($folio, $data['lines'], $request->user()->id);

            return back()->with('success', 'Consumo cargado a la habitación.');
        }

        $resultado = $this->pos->venderPublico(
            $data['lines'],
            (string) $data['payment_method'],
            isset($data['recibido']) ? (float) $data['recibido'] : null,
            $request->user()->id
        );
        $venta = $resultado['venta'];
        $mensaje = 'Venta '.$venta->numero.' cobrada.';
        if ($resultado['cambio'] > 0) {
            $mensaje .= ' Cambio: $'.number_format($resultado['cambio'], 2).'.';
        }

        return back()->with('success', $mensaje)->with('venta_publica', [
            'id' => $venta->id,
            'numero' => $venta->numero,
            'total' => (float) $venta->total,
            'cambio' => $resultado['cambio'],
            'metodo' => $venta->payment_method,
        ]);
    }

    public function catalog(): Response
    {
        return Inertia::render('Hotel/Pos/Catalog', [
            'outlets' => PosOutlet::query()->with('categories.products')->orderBy('name')->get(),
        ]);
    }

    public function storeOutlet(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('pos_outlets', 'code')->where(fn ($q) => $q->where('property_id', CurrentProperty::id())),
            ],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        PosOutlet::create($data);

        return back()->with('success', 'Punto de venta creado.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pos_outlet_id' => ['required', 'exists:pos_outlets,id'],
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active', true);
        PosCategory::create($data);

        return back()->with('success', 'Categoría creada.');
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pos_category_id' => ['required', 'exists:pos_categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:40'],
            'price' => ['required', 'numeric', 'min:0'],
            'iva_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'aplicar_iva' => ['boolean'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'piezas_caja' => ['nullable', 'integer', 'min:1', 'max:999'],
            'bodega' => ['nullable', 'numeric', 'min:0'],
            'exhibicion' => ['nullable', 'numeric', 'min:0'],
            'controla_inventario' => ['boolean'],
            'stock_actual' => ['nullable', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['controla_inventario'] = $request->boolean('controla_inventario');
        $data['stock_actual'] = $data['controla_inventario'] ? ($data['stock_actual'] ?? 0) : 0;
        $data['stock_minimo'] = $data['stock_minimo'] ?? 0;
        $data['costo'] = $data['costo'] ?? 0;
        $data['iva_porcentaje'] = $data['iva_porcentaje'] ?? 16;
        $data['aplicar_iva'] = $request->boolean('aplicar_iva');
        $data['piezas_caja'] = $data['piezas_caja'] ?? 1;
        $data['bodega'] = $data['bodega'] ?? 0;
        $data['exhibicion'] = $data['exhibicion'] ?? 0;
        if (((float) $data['bodega'] + (float) $data['exhibicion']) > 0) {
            $data['stock_actual'] = (float) $data['bodega'] + (float) $data['exhibicion'];
        } elseif ($data['controla_inventario']) {
            $data['bodega'] = $data['stock_actual'];
        }
        PosProduct::create($data);

        return back()->with('success', 'Producto creado.');
    }

    public function updateProduct(Request $request, PosProduct $posProduct): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:40'],
            'price' => ['required', 'numeric', 'min:0'],
            'iva_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'aplicar_iva' => ['boolean'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'piezas_caja' => ['nullable', 'integer', 'min:1', 'max:999'],
            'bodega' => ['nullable', 'numeric', 'min:0'],
            'exhibicion' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['aplicar_iva'] = $request->boolean('aplicar_iva');
        $data['iva_porcentaje'] = $data['iva_porcentaje'] ?? $posProduct->iva_porcentaje;
        $posProduct->update($data);

        return back()->with('success', 'Producto actualizado.');
    }

    public function destroyOutlet(PosOutlet $posOutlet): RedirectResponse
    {
        return CatalogDelete::run($posOutlet, [
            'categorías' => $posOutlet->categories()->exists(),
        ], 'Punto de venta eliminado.');
    }

    public function destroyCategory(PosCategory $posCategory): RedirectResponse
    {
        $this->categoriaDelHotel($posCategory);

        return CatalogDelete::run($posCategory, [
            'productos' => $posCategory->products()->exists(),
        ], 'Categoría eliminada.');
    }

    public function destroyProduct(PosProduct $posProduct): RedirectResponse
    {
        $posProduct->loadMissing('category');
        $this->categoriaDelHotel($posProduct->category);

        return CatalogDelete::run($posProduct, [
            'consumos en folios' => FolioCharge::query()->where('pos_product_id', $posProduct->id)->exists(),
        ], 'Producto eliminado.');
    }

    private function categoriaDelHotel(?PosCategory $category): void
    {
        $category?->loadMissing('outlet');
        abort_unless($category && (int) $category->outlet?->property_id === (int) CurrentProperty::id(), 404);
    }
}
