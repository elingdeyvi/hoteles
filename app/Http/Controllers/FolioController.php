<?php

namespace App\Http\Controllers;

use App\Models\Folio;
use App\Models\FolioCharge;
use App\Services\FolioInvoiceService;
use App\Services\FolioService;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class FolioController extends Controller
{
    public function __construct(
        private readonly FolioService $folios,
        private readonly FolioInvoiceService $invoices
    ) {}

    public function index(Request $request): Response
    {
        $query = Folio::query()
            ->with(['stay.reservation.huesped', 'stay.room', 'charges', 'payments'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return Inertia::render('Hotel/Folios/Index', [
            'folios' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(Folio $folio): Response
    {
        $folio->load(['stay.reservation.huesped', 'stay.room', 'charges', 'payments.receiver']);

        return Inertia::render('Hotel/Folios/Show', [
            'folio' => $folio,
        ]);
    }

    public function addCharge(Request $request, Folio $folio): RedirectResponse
    {
        if ($folio->status !== 'abierto') {
            return back()->with('error', 'El folio está cerrado.');
        }

        $data = $request->validate([
            'concept' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'charge_type' => ['nullable', 'string', 'max:30'],
        ]);

        $this->folios->addCharge(
            $folio,
            $data['concept'],
            (float) $data['amount'],
            $data['charge_type'] ?? 'extra'
        );

        return back()->with('success', 'Cargo registrado.');
    }

    public function destroyCharge(Request $request, Folio $folio, FolioCharge $charge): RedirectResponse
    {
        try {
            $this->folios->removeCharge($folio, $charge, $request->user()?->id);
        } catch (ValidationException $exception) {
            return back()->with('error', collect($exception->errors())->flatten()->first());
        }

        return back()->with('success', 'Cargo quitado.');
    }

    public function addPayment(Request $request, Folio $folio): RedirectResponse
    {
        if ($folio->status !== 'abierto') {
            return back()->with('error', 'El folio está cerrado.');
        }

        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:30'],
            'recibido' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:120'],
        ]);

        $resultado = $this->folios->cobrarYCerrar(
            $folio,
            $data['payment_method'],
            isset($data['recibido']) ? (float) $data['recibido'] : null,
            $request->user()->id,
            $data['reference'] ?? null
        );

        $mensaje = 'Folio cerrado.';
        if ($resultado['saldo'] > 0) {
            $mensaje = 'Cobro registrado y folio cerrado.';
            if ($resultado['cambio'] > 0) {
                $mensaje .= ' Cambio: $'.number_format($resultado['cambio'], 2).'.';
            }
        }

        return back()->with('success', $mensaje);
    }

    public function close(Folio $folio): RedirectResponse
    {
        if ((float) $folio->balance > 0) {
            return back()->with('error', 'No se puede cerrar con saldo pendiente.');
        }

        $this->folios->close($folio);

        return back()->with('success', 'Folio cerrado.');
    }

    public function invoicePdf(Folio $folio): HttpResponse
    {
        $data = $this->invoices->buildViewData($folio);
        $filename = 'factura-'.preg_replace('/[^A-Za-z0-9\-_]/', '_', $folio->folio_number).'.pdf';

        return Pdf::loadView('pdf.folio_factura', $data)
            ->setPaper('letter', 'portrait')
            ->download($filename);
    }
}
