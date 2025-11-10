<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ParkingSpace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BookingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed 10 parking spaces (match your schema: column is `space`)
        for ($i = 1; $i <= 10; $i++) {
            ParkingSpace::create(['space' => "Space Nr. {$i}"]);
        }
    }

    #[Test]
    public function it_creates_a_booking_via_http_and_reserves_days()
    {
        $payload = [
            'reg_plate' => 'AB12 CDE',
            'start_at'  => '2025-12-20 09:00',
            'end_at'    => '2025-12-23 10:00',
        ];

        $res = $this->postJson('/api/bookings', $payload);

        $res->assertCreated()
            ->assertJsonStructure([
                'id', 'space_id', 'reg_plate', 'start_at', 'end_at',
                'total_price_cents', 'status',
                'days' => [['id', 'booking_id', 'space_id', 'date']],
            ]);

        $bookingId = $res->json('id');

        // Inclusive range: 20,21,22,23 → 4 days
        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'reg_plate' => 'AB12 CDE',
        ]);

        $this->assertEquals(
            4,
            \DB::table('booking_days')->where('booking_id', $bookingId)->count()
        );
    }

    #[Test]
    public function it_validates_create_payload()
    {
        $res = $this->postJson('/api/bookings', [
            // missing reg_plate
            'start_at' => '2025-12-20 09:00',
            'end_at'   => '2025-12-23 10:00',
        ]);

        $res->assertStatus(422)->assertJsonValidationErrors(['reg_plate']);
    }

    #[Test]
    public function it_amends_a_booking_via_http()
    {
        $create = $this->postJson('/api/bookings', [
            'reg_plate' => 'ZZ99 XYZ',
            'start_at'  => '2025-12-20 09:00',
            'end_at'    => '2025-12-21 10:00',
        ])->assertCreated();

        $bookingId = $create->json('id');

        // Extend to 23rd
        $patch = $this->patchJson("/api/bookings/{$bookingId}", [
            'end_at' => '2025-12-23 10:00',
        ]);

        $patch->assertOk()->assertJsonPath('id', $bookingId);

        $this->assertEquals(
            4,
            \DB::table('booking_days')->where('booking_id', $bookingId)->count()
        );
    }

    #[Test]
    public function it_cancels_a_booking_via_http_and_removes_days()
    {
        $create = $this->postJson('/api/bookings', [
            'reg_plate' => 'CC11 AAA',
            'start_at'  => '2025-12-20 09:00',
            'end_at'    => '2025-12-22 10:00',
        ])->assertCreated();

        $bookingId = $create->json('id');

        $this->deleteJson("/api/bookings/{$bookingId}")
             ->assertOk()
             ->assertJson(['message' => 'Your booking has been successfully cancelled.']);

        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => Booking::STATUS_CANCELLED,
        ]);

        $this->assertDatabaseCount('booking_days', 0);
    }

    #[Test]
    public function it_returns_availability_per_day()
    {
        // Fill all 10 spaces for two days (20–21)
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/bookings', [
                'reg_plate' => "FILLED-{$i}",
                'start_at'  => '2025-12-20 00:00',
                'end_at'    => '2025-12-21 23:59',
            ])->assertCreated();
        }

        $res = $this->getJson('/api/availability?start_at=2025-12-20 00:00&end_at=2025-12-21 23:59')
                    ->assertOk();

        $data = $res->json();
        $this->assertCount(2, $data);

        foreach ($data as $day) {
            $this->assertSame(10, $day['total_spaces']);
            $this->assertSame(10, $day['booked']);
            $this->assertSame(0,  $day['available']);
        }
    }
}
