<?php

namespace Database\Seeders\Support;

use App\Models\Folio;
use App\Models\FolioCharge;
use App\Models\FolioPayment;
use App\Models\Huesped;
use App\Models\PosProduct;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Stay;
use App\Models\User;
use App\Services\FolioService;
use App\Services\HotelPricingService;
use App\Services\PosSaleService;
use App\Services\ReservationService;
use App\Support\CurrentProperty;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DemoPropertyOperations
{
    private const DEMO_NOTE = '[demo-operacional]';

    private Property $property;

    private User $admin;

    /** @var list<Huesped> */
    private array $guests = [];

    /** @var list<PosProduct> */
    private array $posProducts = [];

    /** @var list<int> */
    private array $roomsInUse = [];

    public function __construct(
        private readonly ReservationService $reservations,
        private readonly HotelPricingService $pricing,
        private readonly FolioService $folios,
        private readonly PosSaleService $posSales
    ) {}

    public function seed(Property $property, User $admin, bool $full = true): void
    {
        $this->property = $property;
        $this->admin = $admin;
        $this->guests = [];
        $this->posProducts = [];
        $this->roomsInUse = [];

        $this->clearPreviousDemo();

        CurrentProperty::set($property);

        $this->loadGuests();
        $this->loadPosProducts();

        if ($this->posProducts === []) {
            CurrentProperty::clear();

            return;
        }

        Room::withoutGlobalScopes()
            ->where('property_id', $property->id)
            ->update(['status' => 'disponible']);

        $this->seedHistoricalStays($full);
        $this->seedLiveOperation($full);
        $this->applyHousekeepingStatuses($full);

        CurrentProperty::clear();
    }

    private function clearPreviousDemo(): void
    {
        Reservation::withoutGlobalScopes()
            ->where('property_id', $this->property->id)
            ->where('notes', self::DEMO_NOTE)
            ->delete();
    }

    private function loadGuests(): void
    {
        $definitions = [
            ['nombre' => 'María González', 'email' => 'maria.gonzalez@demo.hotel', 'telefono' => '5551110101', 'documento' => 'INE-101', 'nacionalidad' => 'MX', 'direccion' => 'Av. Reforma 120, Cuauhtémoc, CDMX', 'notas' => 'Pide piso alto y almohada extra.'],
            ['nombre' => 'Carlos Ruiz', 'email' => 'carlos.ruiz@demo.hotel', 'telefono' => '5551110102', 'documento' => 'INE-102', 'nacionalidad' => 'MX', 'direccion' => 'Calle 60 45, Centro, Mérida', 'notas' => 'Llega tarde; dejó el vuelo en notas.'],
            ['nombre' => 'Ana Martínez', 'email' => 'ana.martinez@demo.hotel', 'telefono' => '5551110103', 'documento' => 'PAS-103', 'nacionalidad' => 'MX', 'direccion' => 'Av. Vallarta 800, Guadalajara', 'notas' => 'Sale hoy. Revisar saldo del folio.'],
            ['nombre' => 'Luis Herrera', 'email' => 'luis.herrera@demo.hotel', 'telefono' => '5551110104', 'documento' => 'INE-104', 'nacionalidad' => 'MX', 'direccion' => 'Blvd. Kukulcán km 9, Cancún', 'notas' => 'Huésped frecuente. Cargo de bar autorizado.'],
            ['nombre' => 'Patricia Vega', 'email' => 'patricia.vega@demo.hotel', 'telefono' => '5551110105', 'documento' => 'INE-105', 'nacionalidad' => 'MX', 'direccion' => 'Av. Universidad 300, Monterrey', 'notas' => 'Llega hoy. Habitación ya asignada.'],
            ['nombre' => 'Roberto Sánchez', 'email' => 'roberto.sanchez@demo.hotel', 'telefono' => '5551110106', 'documento' => 'INE-106', 'nacionalidad' => 'MX', 'direccion' => 'Calle Independencia 15, Oaxaca', 'notas' => 'Llega hoy sin habitación asignada.'],
            ['nombre' => 'Elena Torres', 'email' => 'elena.torres@demo.hotel', 'telefono' => '5551110107', 'documento' => 'INE-107', 'nacionalidad' => 'MX', 'direccion' => 'Paseo de Montejo 200, Mérida', 'notas' => 'Reserva web. Anticipo pendiente.'],
            ['nombre' => 'Jorge Mendoza', 'email' => 'jorge.mendoza@demo.hotel', 'telefono' => '5551110108', 'documento' => 'INE-108', 'nacionalidad' => 'MX', 'direccion' => 'Av. Juárez 50, Puebla', 'notas' => 'Reserva web con anticipo pagado.'],
            ['nombre' => 'Sofía Navarro', 'email' => 'sofia.navarro@demo.hotel', 'telefono' => '5551110109', 'documento' => 'INE-109', 'nacionalidad' => 'MX', 'direccion' => 'Calle 5 de Mayo 8, Querétaro', 'notas' => 'Estancia futura confirmada en recepción.'],
            ['nombre' => 'Diego Castillo', 'email' => 'diego.castillo@demo.hotel', 'telefono' => '5551110110', 'documento' => 'PAS-110', 'nacionalidad' => 'ES', 'direccion' => 'Gran Vía 18, Madrid', 'notas' => 'Viaja con pareja. Habla español e inglés.'],
            ['nombre' => 'Lucía Romero', 'email' => 'lucia.romero@demo.hotel', 'telefono' => '5551110111', 'documento' => 'INE-111', 'nacionalidad' => 'MX', 'direccion' => 'Av. Hidalgo 77, León', 'notas' => 'Reserva cancelada por cambio de fechas.'],
            ['nombre' => 'Andrés Paredes', 'email' => 'andres.paredes@demo.hotel', 'telefono' => '5551110112', 'documento' => 'INE-112', 'nacionalidad' => 'MX', 'direccion' => 'Malecón 40, Veracruz', 'notas' => 'Estancia cerrada del mes. Pagó con transferencia.'],
        ];

        foreach ($definitions as $definition) {
            $this->guests[] = Huesped::query()->updateOrCreate(
                ['email' => $definition['email']],
                $definition
            );
        }
    }

    private function loadPosProducts(): void
    {
        $this->posProducts = PosProduct::query()
            ->with('category.outlet')
            ->whereHas('category.outlet', fn ($q) => $q->where('property_id', $this->property->id))
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->all();
    }

    private function seedHistoricalStays(bool $full): void
    {
        $today = now()->startOfDay();
        $cursor = $today->copy()->startOfMonth();
        $step = $full ? 2 : 4;
        $guestIndex = 0;

        while ($cursor->lt($today->copy()->subDays(2))) {
            $checkIn = $cursor->copy();
            $checkOut = $checkIn->copy()->addDays(2);
            $roomType = $this->randomRoomType();
            $room = $this->pickRoom($roomType, $checkIn, $checkOut);

            if (! $room) {
                $cursor->addDays($step);

                continue;
            }

            $reservation = $this->createReservation(
                $this->guests[$guestIndex % count($this->guests)],
                $roomType,
                $checkIn,
                $checkOut,
                'recepcion',
                'confirmada'
            );
            $guestIndex++;

            $stay = $this->performCheckIn($reservation, $room, $checkIn->copy()->setTime(15, 0));
            $folio = $stay->folio;

            $posDay = $checkOut->copy()->subDay()->setTime(20, 30);
            $this->addPosCharge($folio, $posDay, 1 + ($guestIndex % 2));

            $paymentDay = $checkOut->copy()->setTime(11, 0);
            $this->payFolio($folio, (float) $folio->fresh()->balance, 'tarjeta', $paymentDay);
            $this->performCheckOut($stay, $reservation, $paymentDay);

            $this->roomsInUse = array_values(array_diff($this->roomsInUse, [$room->id]));
            $room->update(['status' => 'sucia']);

            $cursor->addDays($step);
        }
    }

    private function seedLiveOperation(bool $full): void
    {
        $today = now()->startOfDay();

        $this->openStay($this->guests[0], 'A01', $today->copy()->subDays(2), $today->copy()->addDays(2), 0.35, 'efectivo');
        $this->openStay($this->guests[1], 'B01', $today->copy()->subDays(1), $today->copy()->addDays(3), 0, 'tarjeta');

        if ($full) {
            $this->openStay($this->guests[2], 'C01', $today->copy()->subDays(3), $today, 0.8, 'tarjeta');
            $this->openStay($this->guests[3], 'A02', $today->copy()->subDays(4), $today->copy()->addDay(), 1, 'transferencia');
        }

        $this->assignArrival($this->guests[4], $full ? 'B02' : 'A03', $today, $today->copy()->addDays(3));
        $this->createReservation(
            $this->guests[5],
            $this->typeByCodePrefix('A'),
            $today,
            $today->copy()->addDays(2),
            'recepcion',
            'confirmada'
        );

        $this->createReservation(
            $this->guests[8],
            $this->typeByCodePrefix('B'),
            $today->copy()->addDays(4),
            $today->copy()->addDays(7),
            'recepcion',
            'confirmada',
            roomNumber: 'B03'
        );
        $this->createReservation(
            $this->guests[9],
            $this->typeByCodePrefix('C'),
            $today->copy()->addDays(8),
            $today->copy()->addDays(12),
            'recepcion',
            'confirmada',
            roomNumber: 'C02'
        );

        $pending = $this->createReservation(
            $this->guests[6],
            $this->typeByCodePrefix('A'),
            $today->copy()->addDay(),
            $today->copy()->addDays(3),
            'web',
            'pendiente',
            'WEB-'.$this->property->id.'-PEND-01'
        );
        $deposit = round((float) $pending->estimated_total * 0.3, 2);
        $pending->update(['payment_status' => 'pending', 'deposit_amount' => $deposit]);

        $paid = $this->createReservation(
            $this->guests[7],
            $this->typeByCodePrefix('B'),
            $today->copy()->addDays(6),
            $today->copy()->addDays(9),
            'web',
            'pendiente',
            'WEB-'.$this->property->id.'-PAGO-01'
        );
        $paid->update([
            'payment_status' => 'paid',
            'deposit_amount' => round((float) $paid->estimated_total * 0.3, 2),
            'payment_reference' => 'DEMO-ANTICIPO',
            'paid_at' => $today->copy()->subDay()->setTime(18, 20),
        ]);

        if ($full) {
            $this->createReservation(
                $this->guests[6],
                $this->typeByCodePrefix('C'),
                $today->copy()->addDays(10),
                $today->copy()->addDays(13),
                'web',
                'pendiente',
                'WEB-'.$this->property->id.'-PEND-02'
            )->update(['payment_status' => 'pending', 'deposit_amount' => 660]);
        }

        $this->createReservation(
            $this->guests[10],
            $this->typeByCodePrefix('A'),
            $today->copy()->addDays(14),
            $today->copy()->addDays(16),
            'recepcion',
            'cancelada'
        );
    }

    private function openStay(Huesped $guest, string $number, Carbon $checkIn, Carbon $checkOut, float $payRatio, string $method): void
    {
        $room = $this->roomByNumber($number);
        if (! $room || ! $room->roomType) {
            return;
        }

        $reservation = $this->createReservation($guest, $room->roomType, $checkIn, $checkOut, 'recepcion', 'confirmada', roomNumber: $number);
        $stay = $this->performCheckIn($reservation, $room, $checkIn->copy()->setTime(15, 10));
        $this->addPosCharge($stay->folio, $checkIn->copy()->addDay()->setTime(9, 0), 1);
        $this->addPosCharge($stay->folio, now(), 2);

        $balance = (float) $stay->folio->fresh()->balance;
        $payment = round($balance * $payRatio, 2);
        if ($payment > 0) {
            $this->payFolio($stay->folio, $payment, $method, now()->subHour());
        }
    }

    private function assignArrival(Huesped $guest, string $number, Carbon $checkIn, Carbon $checkOut): void
    {
        $room = $this->roomByNumber($number);
        if (! $room || ! $room->roomType) {
            return;
        }

        $this->createReservation($guest, $room->roomType, $checkIn, $checkOut, 'recepcion', 'confirmada', roomNumber: $number);
    }

    private function applyHousekeepingStatuses(bool $full): void
    {
        $idleRooms = Room::withoutGlobalScopes()
            ->where('property_id', $this->property->id)
            ->where('is_active', true)
            ->where('status', 'disponible')
            ->orderBy('id')
            ->get();

        $statuses = $full
            ? ['sucia', 'sucia', 'sucia', 'limpia', 'limpia', 'mantenimiento', 'mantenimiento']
            : ['sucia', 'limpia', 'mantenimiento'];

        foreach ($idleRooms->take(count($statuses)) as $index => $room) {
            $room->update(['status' => $statuses[$index]]);
        }
    }

    private function createReservation(
        Huesped $guest,
        RoomType $roomType,
        Carbon $checkIn,
        Carbon $checkOut,
        string $source,
        string $status,
        ?string $onlineReference = null,
        ?string $roomNumber = null
    ): Reservation {
        $roomId = $roomNumber ? $this->roomByNumber($roomNumber)?->id : null;

        return Reservation::withoutGlobalScopes()->create([
            'property_id' => $this->property->id,
            'folio' => $this->reservations->generateFolio(),
            'huesped_id' => $guest->id,
            'room_type_id' => $roomType->id,
            'room_id' => $roomId,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests_count' => min(2, max(1, (int) $roomType->capacity)),
            'status' => $status,
            'source' => $source,
            'online_reference' => $onlineReference,
            'estimated_total' => $this->pricing->estimateStayTotal($roomType, $checkIn, $checkOut),
            'payment_status' => $source === 'web' ? 'pending' : 'not_required',
            'notes' => self::DEMO_NOTE,
            'created_by' => $this->admin->id,
        ]);
    }

    private function roomByNumber(string $number): ?Room
    {
        return Room::withoutGlobalScopes()
            ->with('roomType')
            ->where('property_id', $this->property->id)
            ->where('number', $number)
            ->first();
    }

    private function typeByCodePrefix(string $letter): RoomType
    {
        $room = $this->roomByNumber($letter.'01');

        return $room?->roomType ?? $this->randomRoomType();
    }

    private function performCheckIn(Reservation $reservation, Room $room, Carbon $at): Stay
    {
        $stay = Stay::create([
            'reservation_id' => $reservation->id,
            'room_id' => $room->id,
            'checked_in_at' => $at,
            'status' => 'activa',
        ]);
        $this->backdate($stay, $at);

        $reservation->update([
            'room_id' => $room->id,
            'status' => 'check_in',
        ]);

        $room->update(['status' => 'ocupada']);
        $this->roomsInUse[] = $room->id;

        $folio = $this->folios->createForStay($stay, (float) $reservation->estimated_total);
        $stay->setRelation('folio', $folio);

        return $stay->load('folio');
    }

    private function performCheckOut(Stay $stay, Reservation $reservation, Carbon $at): void
    {
        $folio = $stay->folio;
        if ($folio && $folio->status === 'abierto') {
            $this->folios->close($folio);
            $this->backdate($folio->fresh(), $at);
        }

        $stay->update([
            'checked_out_at' => $at,
            'status' => 'finalizada',
        ]);
        $this->backdate($stay->fresh(), $at);

        $reservation->update(['status' => 'check_out']);
    }

    private function addPosCharge(Folio $folio, Carbon $at, int $lines): void
    {
        $products = collect($this->posProducts)->shuffle()->take(max(1, $lines));

        foreach ($products as $product) {
            $outletName = $product->category?->outlet?->name ?? 'POS';
            $charge = FolioCharge::create([
                'folio_id' => $folio->id,
                'concept' => "[{$outletName}] {$product->name}",
                'charge_type' => 'pos',
                'amount' => (float) $product->price,
                'quantity' => 1 + ($product->id % 2),
                'pos_product_id' => $product->id,
                'charged_by' => $this->admin->id,
            ]);
            $this->backdate($charge, $at);
        }

        $this->folios->recalculateBalance($folio);
    }

    private function payFolio(Folio $folio, float $amount, string $method, Carbon $at): void
    {
        if ($amount <= 0) {
            return;
        }

        $payment = FolioPayment::create([
            'folio_id' => $folio->id,
            'payment_method' => $method,
            'amount' => $amount,
            'reference' => 'DEMO-'.strtoupper(Str::random(4)),
            'received_by' => $this->admin->id,
        ]);
        $this->backdate($payment, $at);

        $this->folios->recalculateBalance($folio);
    }

    private function pickRoom(RoomType $roomType, Carbon $checkIn, Carbon $checkOut): ?Room
    {
        $rooms = Room::withoutGlobalScopes()
            ->where('property_id', $this->property->id)
            ->where('room_type_id', $roomType->id)
            ->where('is_active', true)
            ->whereNotIn('status', ['mantenimiento'])
            ->whereNotIn('id', $this->roomsInUse)
            ->orderBy('id')
            ->get();

        foreach ($rooms as $room) {
            if ($this->reservations->isRoomAvailable($room, $checkIn, $checkOut)) {
                return $room;
            }
        }

        return null;
    }

    private function randomRoomType(): RoomType
    {
        static $cache = [];

        $key = $this->property->id;
        if (! isset($cache[$key])) {
            $cache[$key] = RoomType::withoutGlobalScopes()
                ->where('property_id', $this->property->id)
                ->orderBy('id')
                ->get()
                ->all();
        }

        $types = $cache[$key];

        return $types[array_rand($types)];
    }

    private function backdate(Model $model, Carbon $at): void
    {
        $model->created_at = $at;
        $model->updated_at = $at;
        $model->saveQuietly();
    }
}
