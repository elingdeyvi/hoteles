<?php

namespace Tests\Feature\Hotel;

use App\Models\Folio;
use App\Models\Huesped;
use App\Models\PosProduct;
use App\Models\Room;
use App\Models\RoomType;
use Tests\HotelTestCase;

class PosSalesReportTest extends HotelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedHotelCatalog();
    }

    public function test_pos_sales_report_reflects_charges(): void
    {
        $huesped = Huesped::query()->firstOrFail();
        $roomType = RoomType::query()->firstOrFail();
        $room = Room::query()->where('room_type_id', $roomType->id)->firstOrFail();

        $reservationId = $this->postJson('/api/hotel/reservations', [
            'huesped_id' => $huesped->id,
            'room_type_id' => $roomType->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
        ])->json('data.id');

        $folioId = $this->postJson("/api/hotel/reservations/{$reservationId}/check-in", [
            'room_id' => $room->id,
        ])->json('data.folio.id');

        $product = PosProduct::query()->where('name', 'Refresco')->firstOrFail();

        $this->postJson('/api/hotel/pos/charge-to-folio', [
            'folio_id' => $folioId,
            'lines' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $from = now()->subDay()->toDateString();
        $to = now()->addDay()->toDateString();

        $response = $this->getJson('/api/hotel/reports/pos-sales', [
            'from' => $from,
            'to' => $to,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.summary.lines_count', 1);

        $this->assertEquals(35.0, (float) $response->json('data.summary.total_sales'));

        $export = $this->get('/api/hotel/reports/pos-sales/export', [
            'from' => $from,
            'to' => $to,
        ]);

        $export->assertOk();
        $this->assertStringContainsString('text/csv', (string) $export->headers->get('content-type'));
        $this->assertStringContainsString('Refresco', $export->streamedContent());
    }
}
