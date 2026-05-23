<?php

namespace App\Http\Controllers;

use App\Models\Folio;
use App\Models\FolioCharge;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HotelDashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $today = now()->toDateString();

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

        $pendingWeb = Reservation::query()
            ->where('source', 'web')
            ->where('status', 'pendiente')
            ->count();

        $pendingWebList = Reservation::query()
            ->with(['huesped', 'roomType'])
            ->where('source', 'web')
            ->where('status', 'pendiente')
            ->orderBy('check_in')
            ->limit(8)
            ->get(['id', 'folio', 'huesped_id', 'room_type_id', 'check_in', 'check_out', 'estimated_total', 'created_at']);

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
            ->selectRaw('pos_outlets.name as outlet_name, COALESCE(SUM(folio_charges.amount * folio_charges.quantity), 0) as total')
            ->groupBy('pos_outlets.name')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'data' => [
                'arrivals_today' => $arrivalsToday,
                'departures_today' => $departuresToday,
                'occupancy_percent' => $occupancy,
                'rooms_occupied' => $occupied,
                'rooms_total' => $totalActive,
                'open_folios' => $openFolios,
                'pending_balance' => $pendingBalance,
                'rooms_by_status' => $byStatus,
                'pending_web_reservations' => $pendingWeb,
                'pending_web_list' => $pendingWebList,
                'pos_sales_today' => $posToday,
                'pos_sales_month' => $posMonth,
                'pos_by_outlet_today' => $posByOutletToday,
            ],
        ]);
    }
}
