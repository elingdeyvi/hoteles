<?php

namespace App\Http\Controllers;

use App\Models\FolioPayment;
use App\Models\Reservation;
use App\Services\PosReportService;
use App\Support\CsvResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HotelReportController extends Controller
{
    public function __construct(
        private readonly PosReportService $posReports
    ) {}

    public function occupancy(Request $request): JsonResponse
    {
        $from = $request->date('from', now()->startOfMonth());
        $to = $request->date('to', now()->endOfMonth());

        $reservations = Reservation::query()
            ->whereNotIn('status', ['cancelada'])
            ->where(function ($q) use ($from, $to): void {
                $q->whereBetween('check_in', [$from, $to])
                    ->orWhereBetween('check_out', [$from, $to]);
            })
            ->selectRaw('DATE(check_in) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return response()->json(['data' => $reservations]);
    }

    public function revenue(Request $request): JsonResponse
    {
        $from = $request->date('from', now()->startOfMonth());
        $to = $request->date('to', now()->endOfMonth());

        $byDay = FolioPayment::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $byRoomType = DB::table('folio_payments')
            ->join('folios', 'folios.id', '=', 'folio_payments.folio_id')
            ->join('stays', 'stays.id', '=', 'folios.stay_id')
            ->join('rooms', 'rooms.id', '=', 'stays.room_id')
            ->join('room_types', 'room_types.id', '=', 'rooms.room_type_id')
            ->whereBetween('folio_payments.created_at', [$from, $to])
            ->selectRaw('room_types.name as room_type, SUM(folio_payments.amount) as total')
            ->groupBy('room_types.name')
            ->get();

        return response()->json([
            'data' => [
                'by_day' => $byDay,
                'by_room_type' => $byRoomType,
            ],
        ]);
    }

    public function arrivalsDepartures(Request $request): JsonResponse
    {
        $date = $request->date('date', now());

        $arrivals = Reservation::query()
            ->with(['huesped', 'roomType', 'room'])
            ->whereDate('check_in', $date)
            ->whereNotIn('status', ['cancelada'])
            ->get();

        $departures = Reservation::query()
            ->with(['huesped', 'roomType', 'room'])
            ->whereDate('check_out', $date)
            ->whereIn('status', ['check_in', 'confirmada'])
            ->get();

        return response()->json(['data' => ['arrivals' => $arrivals, 'departures' => $departures]]);
    }

    public function posSales(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'outlet_id' => ['nullable', 'integer', 'exists:pos_outlets,id'],
        ]);

        $from = Carbon::parse($data['from'] ?? now()->startOfMonth());
        $to = Carbon::parse($data['to'] ?? now());

        return response()->json([
            'data' => $this->posReports->salesReport($from, $to, $data['outlet_id'] ?? null),
        ]);
    }

    public function exportPosSales(Request $request): StreamedResponse
    {
        [$from, $to, $outletId] = $this->parsePosReportParams($request);

        $lines = $this->posReports->salesDetailLines($from, $to, $outletId);
        $rows = $lines->map(fn ($line) => [
            Carbon::parse($line->created_at)->format('Y-m-d H:i'),
            $line->folio_number,
            $line->room_number,
            $line->guest_name,
            $line->outlet_name,
            $line->product_name,
            $line->quantity,
            number_format((float) $line->unit_price, 2, '.', ''),
            number_format((float) $line->line_total, 2, '.', ''),
            $line->charged_by_name ?? '',
        ]);

        $filename = 'pos-ventas_'.$from->format('Ymd').'_'.$to->format('Ymd').'.csv';

        return CsvResponse::download($filename, [
            'Fecha',
            'Folio',
            'Habitación',
            'Huésped',
            'Outlet',
            'Producto',
            'Cantidad',
            'Precio unit.',
            'Total línea',
            'Registrado por',
        ], $rows);
    }

    public function exportRevenue(Request $request): StreamedResponse
    {
        $from = Carbon::parse($request->date('from', now()->startOfMonth()))->startOfDay();
        $to = Carbon::parse($request->date('to', now()))->endOfDay();

        $byDay = FolioPayment::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $byRoomType = DB::table('folio_payments')
            ->join('folios', 'folios.id', '=', 'folio_payments.folio_id')
            ->join('stays', 'stays.id', '=', 'folios.stay_id')
            ->join('rooms', 'rooms.id', '=', 'stays.room_id')
            ->join('room_types', 'room_types.id', '=', 'rooms.room_type_id')
            ->whereBetween('folio_payments.created_at', [$from, $to])
            ->selectRaw('room_types.name as room_type, SUM(folio_payments.amount) as total')
            ->groupBy('room_types.name')
            ->get();

        $rows = collect();
        foreach ($byDay as $row) {
            $rows->push(['Ingresos por día', $row->day, number_format((float) $row->total, 2, '.', '')]);
        }
        foreach ($byRoomType as $row) {
            $rows->push(['Ingresos por tipo habitación', $row->room_type, number_format((float) $row->total, 2, '.', '')]);
        }

        $filename = 'ingresos_'.$from->format('Ymd').'_'.$to->format('Ymd').'.csv';

        return CsvResponse::download($filename, ['Sección', 'Concepto', 'Total'], $rows);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: ?int}
     */
    private function parsePosReportParams(Request $request): array
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'outlet_id' => ['nullable', 'integer', 'exists:pos_outlets,id'],
        ]);

        $from = Carbon::parse($data['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now())->endOfDay();

        return [$from, $to, $data['outlet_id'] ?? null];
    }
}
