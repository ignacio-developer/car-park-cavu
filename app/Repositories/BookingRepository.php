<?php

namespace App\Repositories;

use App\Contracts\BookingRepositoryInterface;
use App\Models\Booking;
use App\Models\BookingDay;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class BookingRepository implements BookingRepositoryInterface
{
    /**
     * Create a new class instance.
     */

    public function find(int $id): ?Booking
    {
        return Booking::with(['space', 'days'])->find($id);
    }

    public function create(array $data): Booking
    {
        return Booking::create($data);
    }

    public function update(Booking $booking, array $data): Booking
    {
        $booking->update($data);
        return $booking;
    } 

    public function delete(Booking $booking): void
    {
        $booking->delete();
    }

    public function replaceDays(Booking $booking, Carbon $fromDay, Carbon $toDay, int $spaceId): void
    {
        // remove old daily entries
        $booking->days()->delete();

        // re-insert new ones (inclusive range)
        $rows = [];
        foreach (CarbonPeriod::create($fromDay, '1 day', $toDay) as $day) {
            $rows[] = [
                'booking_id' => $booking->id,
                'space_id'   => $spaceId,
                'date'       => $day->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($rows)) {
            BookingDay::insert($rows);
        }
    }
}
