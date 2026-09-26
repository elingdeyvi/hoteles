<?php

namespace App\Http\Controllers;

use App\Models\CorteCaja;
use App\Models\FolioPayment;
use App\Models\Reservation;
use App\Services\PosReportService;
use App\Support\CsvResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HotelReportController extends Controller
{
    public function __construct(private readonly PosReportService $posReports) {}

    public function index(Request $request): Response
    {
        $from = Carbon::parse($request->date('from', now()->startOfMonth()))->startOfDay();
        $to = Carbon::parse($request->date('to', now()))->endOfDay();
        $date = Carbon::parse($request->date('date', now()));

        $occupancy = Reservation::query()
            ->whereNotIn('status', ['cancelada'])
            ->where(function ($q) use ($from, $to): void {
                $q->whereBetween('check_in', [$from, $to])
                    ->orWhereBetween('check_out', [$from, $to]);
            })
            ->selectRaw('DATE(check_in) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

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
            ->when(\App\Support\CurrentProperty::id(), fn ($q, $id) => $q->where('rooms.property_id', $id))
            ->selectRaw('room_types.name as room_type, SUM(folio_payments.amount) as total')
            ->groupBy('room_types.name')
            ->get();

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

        $pos = $this->posReports->salesReport($from, $to, $request->integer('outlet_id') ?: null);

        $cortes = CorteCaja::query()
            ->whereHas('caja')
            ->whereBetween('fecha_corte', [$from, $to])
            ->orderByDesc('fecha_corte')
            ->limit(40)
            ->get(['id', 'tipo', 'fecha_corte', 'total_cobros', 'total_esperado', 'total_real', 'diferencia'])
            ->map(fn (CorteCaja $corte) => [
                'id' => $corte->id,
                'tipo' => $corte->tipo,
                'fecha_corte' => $corte->fecha_corte?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
                'total_cobros' => $corte->total_cobros,
                'total_esperado' => $corte->total_esperado,
                'total_real' => $corte->total_real,
                'diferencia' => $corte->diferencia,
            ]);

        return Inertia::render('Hotel/Reports/Index', [
            'filters' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'date' => $date->toDateString(),
            ],
            'occupancy' => $occupancy,
            'revenue' => [
                'by_day' => $byDay,
                'by_room_type' => $byRoomType,
            ],
            'arrivals' => $arrivals,
            'departures' => $departures,
            'pos' => $pos,
            'cortes' => $cortes,
        ]);
    }

    public function exportPosSales(Request $request): StreamedResponse
    {
        $from = Carbon::parse($request->date('from', now()->startOfMonth()))->startOfDay();
        $to = Carbon::parse($request->date('to', now()))->endOfDay();
        $lines = $this->posReports->salesDetailLines($from, $to, $request->integer('outlet_id') ?: null);
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

        return CsvResponse::download('pos-ventas_'.$from->format('Ymd').'_'.$to->format('Ymd').'.csv', [
            'Fecha', 'Folio', 'Habitación', 'Huésped', 'Outlet', 'Producto', 'Cantidad', 'Precio unit.', 'Total línea', 'Registrado por',
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

        $rows = collect();
        foreach ($byDay as $row) {
            $rows->push(['Ingresos por día', $row->day, number_format((float) $row->total, 2, '.', '')]);
        }

        return CsvResponse::download('ingresos_'.$from->format('Ymd').'_'.$to->format('Ymd').'.csv', ['Sección', 'Concepto', 'Total'], $rows);
    }
}
