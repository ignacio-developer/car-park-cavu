<?php

namespace App\Services;

use App\Models\ParkingSpace;
use App\Models\BookingDay;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;


class AvailabilityService
{
    public function findAvailableSpace(Carbon $fromDay, Carbon $toDay, ?int $ignoreBookingId = null): ?int
    {
        return ParkingSpace::whereDoesntHave('days', function ($q) use ($fromDay, $toDay, $ignoreBookingId) {
            $q->whereBetween('date', [$fromDay->toDateString(), $toDay->toDateString()])
              ->whereHas('booking', function ($b) use ($ignoreBookingId) {
                  $b->where('status', 'active');
                  if ($ignoreBookingId) $b->where('bookings.id', '!=', $ignoreBookingId);
              });
        })->orderBy('id')->value('id');
    }

    public function checkAvailability(string $startAt, string $endAt): array
    {
        $from = Carbon::parse($startAt)->startOfDay();
        $to   = Carbon::parse($endAt)->startOfDay();

        $totalSpaces = ParkingSpace::count();

        // booked counts per day (only active bookings)
        $bookedByDate = BookingDay::query()
            ->select('date', DB::raw('COUNT(*) as booked'))
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->whereHas('booking', fn($q) => $q->where('status', 'active'))
            ->groupBy('date')
            ->pluck('booked', 'date'); // ['2025-12-20' => 9, ...]

        // build full range (including days with 0 bookings)
        $result = [];
        foreach (CarbonPeriod::create($from, '1 day', $to) as $day) {
            $d = $day->toDateString();
            $booked = (int) ($bookedByDate[$d] ?? 0);
            $available = max($totalSpaces - $booked, 0);

            $result[] = [
                'date'          => $d,
                'total_spaces'  => $totalSpaces,
                'booked'        => $booked,
                'available'     => $available,
            ];
        }

        return $result;
    }

}
