<?php

namespace App\Http\Controllers;

use App\Models\Folio;
use App\Services\FolioInvoiceService;
use App\Services\FolioService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FolioController extends Controller
{
    public function __construct(
        private readonly FolioService $folios,
        private readonly FolioInvoiceService $invoices
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Folio::query()
            ->with(['stay.reservation.huesped', 'stay.room', 'charges', 'payments'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json(['data' => $query->paginate(20)]);
    }

    public function show(Folio $folio): JsonResponse
    {
        return response()->json([
            'data' => $folio->load(['stay.reservation.huesped', 'stay.room', 'charges', 'payments.receiver']),
        ]);
    }

    public function addCharge(Request $request, Folio $folio): JsonResponse
    {
        if ($folio->status !== 'abierto') {
            return response()->json(['message' => 'El folio está cerrado.'], 422);
        }

        $data = $request->validate([
            'concept' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'charge_type' => ['nullable', 'string', 'max:30'],
        ]);

        $charge = $this->folios->addCharge(
            $folio,
            $data['concept'],
            (float) $data['amount'],
            $data['charge_type'] ?? 'extra'
        );

        return response()->json(['data' => $charge, 'folio' => $folio->fresh(['charges', 'payments'])]);
    }

    public function addPayment(Request $request, Folio $folio): JsonResponse
    {
        if ($folio->status !== 'abierto') {
            return response()->json(['message' => 'El folio está cerrado.'], 422);
        }

        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:30'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $payment = $this->folios->registerPayment(
            $folio,
            $data['payment_method'],
            (float) $data['amount'],
            $request->user()->id,
            $data['reference'] ?? null
        );

        return response()->json(['data' => $payment, 'folio' => $folio->fresh(['charges', 'payments'])]);
    }

    public function close(Folio $folio): JsonResponse
    {
        if ((float) $folio->balance > 0) {
            return response()->json(['message' => 'No se puede cerrar con saldo pendiente.'], 422);
        }

        $closed = $this->folios->close($folio);

        return response()->json(['data' => $closed->load(['stay.reservation.huesped', 'stay.room', 'charges', 'payments'])]);
    }

    public function invoicePdf(Folio $folio): Response
    {
        $data = $this->invoices->buildViewData($folio);
        $filename = 'factura-'.preg_replace('/[^A-Za-z0-9\-_]/', '_', $folio->folio_number).'.pdf';

        return Pdf::loadView('pdf.folio_factura', $data)
            ->setPaper('letter', 'portrait')
            ->download($filename);
    }
}
