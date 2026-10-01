<?php

namespace App\Http\Controllers;

use App\Models\Folio;
use App\Models\FolioCharge;
use App\Models\FolioPayment;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $today = now()->toDateString();
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', $today))->endOfDay();
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $arrivalsToday = Reservation::query()
            ->whereDate('check_in', $today)
            ->whereNotIn('status', ['cancelada'])
            ->count();

        $departuresToday = Reservation::query()
            ->whereDate('check_out', $today)
            ->whereIn('status', ['check_in', 'confirmada'])
            ->count();

        $occupied = Room::query()->where('status', 'ocupada')->count();
        $totalActive = Room::query()->where('is_active', true)->count();
        $occupancy = $totalActive > 0 ? round(($occupied / $totalActive) * 100, 1) : 0;

        $openFolios = Folio::query()->where('status', 'abierto')->count();
        $pendingBalance = (float) Folio::query()->where('status', 'abierto')->sum('balance');

        $byStatus = Room::query()
            ->where('is_active', true)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingWebList = Reservation::query()
            ->with(['huesped:id,nombre', 'roomType:id,name'])
            ->where('source', 'web')
            ->where('status', 'pendiente')
            ->orderBy('check_in')
            ->limit(8)
            ->get();

        $posToday = (float) FolioCharge::query()
            ->where('charge_type', 'pos')
            ->whereNotNull('pos_product_id')
            ->whereDate('created_at', $today)
            ->selectRaw('COALESCE(SUM(amount * quantity), 0) as total')
            ->value('total');

        $posMonth = (float) FolioCharge::query()
            ->where('charge_type', 'pos')
            ->whereNotNull('pos_product_id')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('COALESCE(SUM(amount * quantity), 0) as total')
            ->value('total');

        $posByOutletToday = DB::table('folio_charges')
            ->join('pos_products', 'pos_products.id', '=', 'folio_charges.pos_product_id')
            ->join('pos_categories', 'pos_categories.id', '=', 'pos_products.pos_category_id')
            ->join('pos_outlets', 'pos_outlets.id', '=', 'pos_categories.pos_outlet_id')
            ->where('folio_charges.charge_type', 'pos')
            ->whereDate('folio_charges.created_at', $today)
            ->when(\App\Support\CurrentProperty::id(), function ($q, $propertyId) {
                $q->where('pos_outlets.property_id', $propertyId);
            })
            ->selectRaw('pos_outlets.name as outlet_name, COALESCE(SUM(folio_charges.amount * folio_charges.quantity), 0) as total')
            ->groupBy('pos_outlets.name')
            ->orderByDesc('total')
            ->get();

        $arrivalsByDay = Reservation::query()
            ->whereNotIn('status', ['cancelada'])
            ->whereBetween('check_in', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('DATE(check_in) as day, count(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $revenueByDay = FolioPayment::query()
            ->whereHas('folio.stay.room')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $posByDay = FolioCharge::query()
            ->where('charge_type', 'pos')
            ->whereHas('posProduct.category.outlet')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(amount * quantity), 0) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $series = [];
        foreach (CarbonPeriod::create($from->toDateString(), $to->toDateString()) as $day) {
            $key = $day->toDateString();
            $series[] = [
                'day' => $key,
                'arrivals' => (int) ($arrivalsByDay[$key] ?? 0),
                'revenue' => round((float) ($revenueByDay[$key] ?? 0), 2),
                'pos' => round((float) ($posByDay[$key] ?? 0), 2),
            ];
        }

        return Inertia::render('Dashboard', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'series' => $series,
            'summary' => [
                'arrivals_today' => $arrivalsToday,
                'departures_today' => $departuresToday,
                'occupancy_percent' => $occupancy,
                'rooms_occupied' => $occupied,
                'rooms_total' => $totalActive,
                'open_folios' => $openFolios,
                'pending_balance' => $pendingBalance,
                'rooms_by_status' => $byStatus,
                'pending_web_reservations' => $pendingWebList->count(),
                'pending_web_list' => $pendingWebList,
                'pos_sales_today' => $posToday,
                'pos_sales_month' => $posMonth,
                'pos_by_outlet_today' => $posByOutletToday,
                'range_arrivals' => array_sum(array_column($series, 'arrivals')),
                'range_revenue' => round(array_sum(array_column($series, 'revenue')), 2),
                'range_pos' => round(array_sum(array_column($series, 'pos')), 2),
            ],
        ]);
    }
}
