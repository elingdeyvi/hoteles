<?php

namespace App\Services;

use App\Models\RoomRate;
use App\Models\RoomType;
use App\Models\SeasonRate;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class HotelPricingService
{
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
