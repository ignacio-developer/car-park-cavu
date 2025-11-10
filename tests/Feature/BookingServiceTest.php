<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\ParkingSpaceSeeder;
use App\Services\BookingService;
use App\Models\Booking;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // create 10 spaces
        $this->seed(ParkingSpaceSeeder::class);

        // resolve with container (uses your bindings)
        $this->service = app(BookingService::class);
    }

    #[Test]
    public function it_creates_a_booking_and_reserves_days()
    {
        $booking = $this->service->create([
            'reg_plate' => 'AB12 CDE',
            'start_at'  => '2025-12-20 09:00',
            'end_at'    => '2025-12-23 10:00',
        ]);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertCount(4, $booking->days); // inclusive days
    }

    #[Test]
    public function it_amends_a_booking_and_updates_days()
    {
        $booking = $this->service->create([
            'reg_plate' => 'AB12 CDE',
            'start_at'  => '2025-12-20 09:00',
            'end_at'    => '2025-12-22 10:00',
        ]);

        $amended = $this->service->amend($booking, [
            'end_at' => '2025-12-23 10:00',
        ]);

        $this->assertCount(4, $amended->days);
    }

    #[Test]
    public function it_cancels_a_booking_and_removes_days()
    {
        $booking = $this->service->create([
            'reg_plate' => 'AB12 CDE',
            'start_at'  => '2025-12-20 09:00',
            'end_at'    => '2025-12-22 10:00',
        ]);

        $this->service->cancel($booking);

        $booking->refresh();
        $this->assertEquals(Booking::STATUS_CANCELLED, $booking->status);
        $this->assertCount(0, $booking->days);
    }
}
