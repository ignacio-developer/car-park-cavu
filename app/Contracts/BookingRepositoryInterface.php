<?php

namespace App\Contracts;

use App\Models\Booking;
use Carbon\Carbon;


interface BookingRepositoryInterface
{
    public function find(int $id): ?Booking;

    public function create(array $data): Booking;

    public function update(Booking $booking, array $data): Booking;

    public function delete(Booking $booking): void;

    /** Replace the booking_days for the given booking */
    public function replaceDays(Booking $booking, Carbon $fromDay, Carbon $toDay, int $spaceId): void;
}