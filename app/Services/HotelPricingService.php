<?php

namespace App\Services;

use App\Models\RoomRate;
use App\Models\RoomType;
use App\Models\SeasonRate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class HotelPricingService
{
    /**
     * @return array{hospedaje: float, extra: float, total: float, tarifa: float, tarifa_persona_extra: float}
     */
    public function cotizar(
        RoomType $roomType,
        Carbon $checkIn,
        Carbon $checkOut,
        string $modalidad,
        int $horas,
        int $personasExtra,
    ): array {
        if ($modalidad === 'horas') {
            $tarifa = (float) ($roomType->hourly_price ?? 0);
            $hospedaje = round($tarifa * max(1, $horas), 2);
        } else {
            $hospedaje = $this->estimateStayTotal($roomType, $checkIn, $checkOut);
            $noches = max(1, $checkIn->diffInDays($checkOut));
            $tarifa = round($hospedaje / $noches, 2);
        }

        $tarifaExtra = (float) $roomType->extra_person_price;
        $extra = round($tarifaExtra * max(0, $personasExtra), 2);

        return [
            'hospedaje' => $hospedaje,
            'extra' => $extra,
            'total' => round($hospedaje + $extra, 2),
            'tarifa' => $tarifa,
            'tarifa_persona_extra' => $tarifaExtra,
        ];
    }

    public function estimateStayTotal(RoomType $roomType, Carbon $checkIn, Carbon $checkOut): float
    {
        $total = 0.0;
        $period = CarbonPeriod::create($checkIn, $checkOut->copy()->subDay());

        foreach ($period as $date) {
            $total += $this->priceForNight($roomType, $date);
        }

        return round($total, 2);
    }

    public function priceForNight(RoomType $roomType, Carbon $date): float
    {
        $rate = RoomRate::query()
            ->where('room_type_id', $roomType->id)
            ->where('is_active', true)
            ->where('is_weekend', $date->isWeekend())
            ->orderByDesc('id')
            ->first();

        $base = $rate ? (float) $rate->price : (float) $roomType->base_price;

        if ($rate) {
            $season = SeasonRate::query()
                ->where('room_rate_id', $rate->id)
                ->where('is_active', true)
                ->whereDate('starts_on', '<=', $date)
                ->whereDate('ends_on', '>=', $date)
                ->first();

            if ($season) {
                if ($season->price_override !== null) {
                    return (float) $season->price_override;
                }

                return round($base * (float) $season->multiplier, 2);
            }
        }

        return $base;
    }
}
