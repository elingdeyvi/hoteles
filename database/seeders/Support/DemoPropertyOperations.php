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

        if ($this->alreadySeeded()) {
            return;
        }

        CurrentProperty::set($property);

        $this->loadGuests($full);
        $this->loadPosProducts();

        if ($this->posProducts === []) {
            return;
        }

        $this->seedHistoricalStays($full);
        $this->seedTodayOperations($full);
        $this->seedPendingWebReservations($full ? 4 : 2);
        $this->seedFutureReservations($full ? 3 : 1);
        $this->seedCancelledReservation();
        $this->applyHousekeepingStatuses($full);

        CurrentProperty::clear();
    }

    private function alreadySeeded(): bool
    {
        return Reservation::withoutGlobalScopes()
            ->where('property_id', $this->property->id)
            ->where('notes', self::DEMO_NOTE)
            ->exists();
    }

    private function loadGuests(bool $full): void
    {
        $definitions = [
            ['nombre' => 'María González', 'email' => 'maria.gonzalez@demo.hotel', 'documento' => 'DEM-101'],
            ['nombre' => 'Carlos Ruiz', 'email' => 'carlos.ruiz@demo.hotel', 'documento' => 'DEM-102'],
            ['nombre' => 'Ana Martínez', 'email' => 'ana.martinez@demo.hotel', 'documento' => 'DEM-103'],
            ['nombre' => 'Luis Herrera', 'email' => 'luis.herrera@demo.hotel', 'documento' => 'DEM-104'],
            ['nombre' => 'Patricia Vega', 'email' => 'patricia.vega@demo.hotel', 'documento' => 'DEM-105'],
            ['nombre' => 'Roberto Sánchez', 'email' => 'roberto.sanchez@demo.hotel', 'documento' => 'DEM-106'],
            ['nombre' => 'Elena Torres', 'email' => 'elena.torres@demo.hotel', 'documento' => 'DEM-107'],
            ['nombre' => 'Jorge Mendoza', 'email' => 'jorge.mendoza@demo.hotel', 'documento' => 'DEM-108'],
        ];

        if (! $full) {
            $definitions = array_slice($definitions, 0, 4);
        }

        foreach ($definitions as $definition) {
            $this->guests[] = Huesped::firstOrCreate(
                ['email' => $definition['email']],
                [
                    'nombre' => $definition['nombre'],
                    'telefono' => '555'.random_int(1000000, 9999999),
                    'documento' => $definition['documento'],
                    'nacionalidad' => 'MX',
                ]
            );
        }
    }

    private function loadPosProducts(): void
    {
        $this->posProducts = PosProduct::query()
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

    private function seedTodayOperations(bool $full): void
    {
        $today = now()->startOfDay();

        $this->createReservation(
            $this->guests[0],
            $this->randomRoomType(),
            $today,
            $today->copy()->addDays(3),
            'recepcion',
            'confirmada'
        );

        $departureType = $this->randomRoomType();
        $departureRoom = $this->pickRoom($departureType, $today->copy()->subDays(2), $today);
        if ($departureRoom) {
            $departureReservation = $this->createReservation(
                $this->guests[1],
                $departureType,
                $today->copy()->subDays(2),
                $today,
                'recepcion',
                'confirmada'
            );
            $departureStay = $this->performCheckIn(
                $departureReservation,
                $departureRoom,
                $today->copy()->subDays(2)->setTime(14, 0)
            );
            $this->addPosCharge($departureStay->folio, $today->copy()->setTime(8, 15), 1);
        }

        $inHouseType = $this->randomRoomType();
        $inHouseRoom = $this->pickRoom($inHouseType, $today->copy()->subDays(1), $today->copy()->addDays(2));
        if ($inHouseRoom) {
            $inHouseReservation = $this->createReservation(
                $this->guests[2],
                $inHouseType,
                $today->copy()->subDays(1),
                $today->copy()->addDays(2),
                'recepcion',
                'confirmada'
            );
            $inHouseStay = $this->performCheckIn(
                $inHouseReservation,
                $inHouseRoom,
                $today->copy()->subDays(1)->setTime(16, 30)
            );
            $this->addPosCharge($inHouseStay->folio, now(), 2);
            $this->addPosCharge($inHouseStay->folio, $today->copy()->setTime(13, 0), 1);
        }

        if ($full) {
            $balanceType = $this->randomRoomType();
            $balanceRoom = $this->pickRoom($balanceType, $today, $today->copy()->addDays(4));
            if ($balanceRoom) {
                $balanceReservation = $this->createReservation(
                    $this->guests[3],
                    $balanceType,
                    $today,
                    $today->copy()->addDays(4),
                    'recepcion',
                    'confirmada'
                );
                $balanceStay = $this->performCheckIn($balanceReservation, $balanceRoom, $today->copy()->setTime(12, 0));
                $this->addPosCharge($balanceStay->folio, $today->copy()->setTime(12, 45), 2);
                $partial = round((float) $balanceStay->folio->fresh()->balance * 0.4, 2);
                if ($partial > 0) {
                    $this->payFolio($balanceStay->folio, $partial, 'efectivo', $today->copy()->setTime(13, 30));
                }
            }
        }
    }

    private function seedPendingWebReservations(int $count): void
    {
        $today = now()->startOfDay();

        for ($i = 0; $i < $count; $i++) {
            $checkIn = $today->copy()->addDays($i + 1);
            $this->createReservation(
                $this->guests[$i % count($this->guests)],
                $this->randomRoomType(),
                $checkIn,
                $checkIn->copy()->addDays(2 + ($i % 2)),
                'web',
                'pendiente',
                'WEB-'.strtoupper(Str::random(6))
            );
        }
    }

    private function seedFutureReservations(int $count): void
    {
        $today = now()->startOfDay();

        for ($i = 0; $i < $count; $i++) {
            $checkIn = $today->copy()->addDays(5 + ($i * 2));
            $this->createReservation(
                $this->guests[($i + 2) % count($this->guests)],
                $this->randomRoomType(),
                $checkIn,
                $checkIn->copy()->addDays(3),
                'recepcion',
                'confirmada'
            );
        }
    }

    private function seedCancelledReservation(): void
    {
        $today = now()->startOfDay();

        $this->createReservation(
            $this->guests[0],
            $this->randomRoomType(),
            $today->copy()->addDays(10),
            $today->copy()->addDays(12),
            'recepcion',
            'cancelada'
        );
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
        ?string $onlineReference = null
    ): Reservation {
        return Reservation::withoutGlobalScopes()->create([
            'property_id' => $this->property->id,
            'folio' => $this->reservations->generateFolio(),
            'huesped_id' => $guest->id,
            'room_type_id' => $roomType->id,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'guests_count' => min(2, max(1, $roomType->capacity)),
            'status' => $status,
            'source' => $source,
            'online_reference' => $onlineReference,
            'estimated_total' => $this->pricing->estimateStayTotal($roomType, $checkIn, $checkOut),
            'payment_status' => $source === 'web' ? 'pendiente' : 'pagado',
            'notes' => self::DEMO_NOTE,
            'created_by' => $this->admin->id,
        ]);
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
