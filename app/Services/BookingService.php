<?php

namespace App\Services;

use App\Contracts\BookingRepositoryInterface;
use App\Models\Booking;
use App\Models\ParkingSpace;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;
use App\Services\PricingService;
use App\Services\AvailabilityService;

class BookingService
{
    protected BookingRepositoryInterface $bookings;
    protected PricingService $pricingService;
    protected AvailabilityService $availabilityService;

    public function __construct(BookingRepositoryInterface $bookings, PricingService $pricingService, AvailabilityService $availabilityService) 
    {
        $this->bookings = $bookings;
        $this->pricingService = $pricingService;
        $this->availabilityService = $availabilityService;
    }

    /**
     * Create a new booking
     */
    public function create(array $data): Booking
    {

        [$fromAt, $toAt] = [Carbon::parse($data['start_at']), Carbon::parse($data['end_at'])];
        [$fromDay, $toDay] = [$fromAt->copy()->startOfDay(), $toAt->copy()->startOfDay()];

        // find an available space
        $spaceId = $this->availabilityService->findAvailableSpace($fromDay, $toDay);
        if (!$spaceId) {
            abort(409, 'No spaces available for the selected dates.');
        }

        // Getting the price from pricing service.
        $priceData  = $this->pricingService->calculate($fromDay, $toDay);
        $priceCents = $priceData['total_price_cents'];

        
        return DB::transaction(function () use ($data, $fromAt, $toAt, $spaceId, $fromDay, $toDay, $priceCents) {
            $booking = $this->bookings->create([
                'space_id'          => $spaceId,
                'start_at'          => $fromAt,
                'end_at'            => $toAt,
                'reg_plate'         => $data['reg_plate'],
                'total_price_cents' => $priceCents,
                'status'            => Booking::STATUS_ACTIVE,
            ]);

            $this->bookings->replaceDays($booking, $fromDay, $toDay, $spaceId);

            return $booking->load(['space', 'days']);
        });
        
    }

    /**
     * Amend an existing booking
     */
    public function amend(Booking $booking, array $changes): Booking
    {
        $fromAt = isset($changes['start_at']) ? Carbon::parse($changes['start_at']) : $booking->start_at;
        $toAt   = isset($changes['end_at'])   ? Carbon::parse($changes['end_at'])   : $booking->end_at;

        [$fromDay, $toDay] = [$fromAt->copy()->startOfDay(), $toAt->copy()->startOfDay()];

        $spaceId = $this->availabilityService->findAvailableSpace($fromDay, $toDay, $booking->id);
        if (!$spaceId) {
            abort(409, 'No spaces available for the amended dates.');
        }

        //Again calculating the price at the service.
        $priceData  = $this->pricingService->calculate($fromDay, $toDay);
        $priceCents = $priceData['total_price_cents'];

        return DB::transaction(function () use ($booking, $changes, $fromAt, $toAt, $fromDay, $toDay, $spaceId, $priceCents) {
            $data = array_merge($changes, [
                'space_id'          => $spaceId,
                'start_at'          => $fromAt,
                'end_at'            => $toAt,
                'total_price_cents' => $priceCents,
            ]);

            $this->bookings->update($booking, $data);
            $this->bookings->replaceDays($booking, $fromDay, $toDay, $spaceId);

            return $booking->fresh(['space', 'days']);
        });
    }

    /**
     * Cancel booking, updates the booking status to 'inactive' and delete booking_days.
     */
    public function cancel(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            $this->bookings->update($booking, ['status' => Booking::STATUS_CANCELLED]);
            $booking->days()->delete();
        });
    }

}