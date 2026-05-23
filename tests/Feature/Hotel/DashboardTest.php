<?php

namespace Tests\Feature\Hotel;

use Tests\HotelTestCase;

class DashboardTest extends HotelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedHotelCatalog();
    }

    public function test_dashboard_summary_returns_kpis(): void
    {
        $response = $this->getJson('/api/dashboard/resumen');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'arrivals_today',
                    'departures_today',
                    'occupancy_percent',
                    'rooms_occupied',
                    'rooms_total',
                    'open_folios',
                    'pending_balance',
                    'rooms_by_status',
                    'pending_web_reservations',
                    'pending_web_list',
                    'pos_sales_today',
                    'pos_sales_month',
                    'pos_by_outlet_today',
                ],
            ]);

        $this->assertGreaterThan(0, $response->json('data.rooms_total'));
    }

    public function test_dashboard_includes_pos_sales_after_charge(): void
    {
        $huesped = \App\Models\Huesped::query()->firstOrFail();
        $roomType = \App\Models\RoomType::query()->firstOrFail();
        $room = \App\Models\Room::query()->where('room_type_id', $roomType->id)->firstOrFail();
        $product = \App\Models\PosProduct::query()->firstOrFail();

        $reservationId = $this->postJson('/api/hotel/reservations', [
            'huesped_id' => $huesped->id,
            'room_type_id' => $roomType->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
        ])->json('data.id');

        $folioId = $this->postJson("/api/hotel/reservations/{$reservationId}/check-in", [
            'room_id' => $room->id,
        ])->json('data.folio.id');

        $this->postJson('/api/hotel/pos/charge-to-folio', [
            'folio_id' => $folioId,
            'lines' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $summary = $this->getJson('/api/dashboard/resumen')->json('data');

        $this->assertGreaterThan(0, (float) $summary['pos_sales_today']);
        $this->assertNotEmpty($summary['pos_by_outlet_today']);
    }
}
