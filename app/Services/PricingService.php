<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PricingService
{
    
    public function calculate(Carbon $from, Carbon $to): array
    {
        $total = 0;
        $dailyBreakdown = [];

        foreach (CarbonPeriod::create($from, '1 day', $to) as $day) {
            // We will use £15 for weekend rate and £12 for weekdays.
            $dailyRate = in_array($day->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY])
                ? 1500
                : 1200;

            $isWeekend = in_array($day->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY]);
            $isSummer = in_array($day->month, [6, 7, 8]);

            // Add summer surcharge, £3.
            if ($isSummer) {
                $dailyRate += 300;
            }

            $dailyBreakdown[] = [
                'date'       => $day->toDateString(),
                'is_weekend' => $isWeekend,
                'is_summer'  => $isSummer,
                'price_cents'=> $dailyRate,
            ];

            $total += $dailyRate;
        }

        return [
            'total_price_cents' => $total,
            'days'              => count($dailyBreakdown),
            'daily'             => $dailyBreakdown,
        ];
    }

}
