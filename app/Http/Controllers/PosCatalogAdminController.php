<?php

namespace App\Http\Controllers;

use App\Models\PosCategory;
use App\Models\PosOutlet;
use App\Models\PosProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosCatalogAdminController extends Controller
{
    public function outlets(): JsonResponse
    {
        return response()->json([
            'data' => PosOutlet::query()->with('categories.products')->orderBy('name')->get(),
        ]);
    }

    public function storeOutlet(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', 'unique:pos_outlets,code'],
            'is_active' => ['boolean'],
        ]);

        return response()->json(['data' => PosOutlet::create($data)], 201);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pos_outlet_id' => ['required', 'exists:pos_outlets,id'],
            'name' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        return response()->json(['data' => PosCategory::create($data)], 201);
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pos_category_id' => ['required', 'exists:pos_categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:40'],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        return response()->json(['data' => PosProduct::create($data)->load('category.outlet')], 201);
    }

    public function updateProduct(Request $request, PosProduct $posProduct): JsonResponse
    {
        $data = $request->validate([
            'pos_category_id' => ['sometimes', 'exists:pos_categories,id'],
            'name' => ['sometimes', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:40'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $posProduct->update($data);

        return response()->json(['data' => $posProduct->fresh('category.outlet')]);
    }
}
