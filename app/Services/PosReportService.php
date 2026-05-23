<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PosReportService
{
    public function salesReport(Carbon $from, Carbon $to, ?int $outletId = null): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $base = $this->posChargesQuery($from, $to, $outletId);

        $lineTotalSql = '(folio_charges.amount * folio_charges.quantity)';

        $summary = (clone $base)
            ->selectRaw("COUNT(*) as lines_count, COALESCE(SUM({$lineTotalSql}), 0) as total_sales")
            ->first();

        $byDay = (clone $base)
            ->selectRaw("DATE(folio_charges.created_at) as day, COUNT(*) as lines_count, COALESCE(SUM({$lineTotalSql}), 0) as total")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $byOutlet = DB::table('folio_charges')
            ->join('pos_products', 'pos_products.id', '=', 'folio_charges.pos_product_id')
            ->join('pos_categories', 'pos_categories.id', '=', 'pos_products.pos_category_id')
            ->join('pos_outlets', 'pos_outlets.id', '=', 'pos_categories.pos_outlet_id')
            ->where('folio_charges.charge_type', 'pos')
            ->whereNotNull('folio_charges.pos_product_id')
            ->whereBetween('folio_charges.created_at', [$from, $to])
            ->when($outletId, fn ($q) => $q->where('pos_outlets.id', $outletId))
            ->selectRaw("pos_outlets.id as outlet_id, pos_outlets.name as outlet_name, COUNT(*) as lines_count, COALESCE(SUM({$lineTotalSql}), 0) as total")
            ->groupBy('pos_outlets.id', 'pos_outlets.name')
            ->orderByDesc('total')
            ->get();

        $topProducts = DB::table('folio_charges')
            ->join('pos_products', 'pos_products.id', '=', 'folio_charges.pos_product_id')
            ->join('pos_categories', 'pos_categories.id', '=', 'pos_products.pos_category_id')
            ->join('pos_outlets', 'pos_outlets.id', '=', 'pos_categories.pos_outlet_id')
            ->where('folio_charges.charge_type', 'pos')
            ->whereNotNull('folio_charges.pos_product_id')
            ->whereBetween('folio_charges.created_at', [$from, $to])
            ->when($outletId, fn ($q) => $q->where('pos_outlets.id', $outletId))
            ->selectRaw("pos_products.id as product_id, pos_products.name as product_name, pos_outlets.name as outlet_name, SUM(folio_charges.quantity) as units, COALESCE(SUM({$lineTotalSql}), 0) as total")
            ->groupBy('pos_products.id', 'pos_products.name', 'pos_outlets.name')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        $outlets = DB::table('pos_outlets')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $totalSales = (float) ($summary->total_sales ?? 0);
        $linesCount = (int) ($summary->lines_count ?? 0);

        return [
            'summary' => [
                'total_sales' => $totalSales,
                'lines_count' => $linesCount,
                'average_ticket' => $linesCount > 0 ? round($totalSales / $linesCount, 2) : 0,
            ],
            'by_day' => $byDay,
            'by_outlet' => $byOutlet,
            'top_products' => $topProducts,
            'outlets' => $outlets,
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function salesDetailLines(Carbon $from, Carbon $to, ?int $outletId = null): \Illuminate\Support\Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        $lineTotalSql = '(folio_charges.amount * folio_charges.quantity)';

        return DB::table('folio_charges')
            ->join('folios', 'folios.id', '=', 'folio_charges.folio_id')
            ->join('stays', 'stays.id', '=', 'folios.stay_id')
            ->join('rooms', 'rooms.id', '=', 'stays.room_id')
            ->join('reservations', 'reservations.id', '=', 'stays.reservation_id')
            ->join('huespedes', 'huespedes.id', '=', 'reservations.huesped_id')
            ->join('pos_products', 'pos_products.id', '=', 'folio_charges.pos_product_id')
            ->join('pos_categories', 'pos_categories.id', '=', 'pos_products.pos_category_id')
            ->join('pos_outlets', 'pos_outlets.id', '=', 'pos_categories.pos_outlet_id')
            ->leftJoin('users', 'users.id', '=', 'folio_charges.charged_by')
            ->where('folio_charges.charge_type', 'pos')
            ->whereNotNull('folio_charges.pos_product_id')
            ->whereBetween('folio_charges.created_at', [$from, $to])
            ->when($outletId, fn ($q) => $q->where('pos_outlets.id', $outletId))
            ->orderBy('folio_charges.created_at')
            ->select([
                'folio_charges.created_at',
                'folios.folio_number',
                'rooms.number as room_number',
                'huespedes.nombre as guest_name',
                'pos_outlets.name as outlet_name',
                'pos_products.name as product_name',
                'folio_charges.quantity',
                'folio_charges.amount as unit_price',
                DB::raw("{$lineTotalSql} as line_total"),
                'users.name as charged_by_name',
            ])
            ->get();
    }

    private function posChargesQuery(Carbon $from, Carbon $to, ?int $outletId): \Illuminate\Database\Query\Builder
    {
        return DB::table('folio_charges')
            ->join('pos_products', 'pos_products.id', '=', 'folio_charges.pos_product_id')
            ->join('pos_categories', 'pos_categories.id', '=', 'pos_products.pos_category_id')
            ->join('pos_outlets', 'pos_outlets.id', '=', 'pos_categories.pos_outlet_id')
            ->where('folio_charges.charge_type', 'pos')
            ->whereNotNull('folio_charges.pos_product_id')
            ->whereBetween('folio_charges.created_at', [$from, $to])
            ->when($outletId, fn ($q) => $q->where('pos_outlets.id', $outletId));
    }
}
