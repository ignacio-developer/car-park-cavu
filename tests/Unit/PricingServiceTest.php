<?php

namespace Tests\Unit;

use App\Services\PricingService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class PricingServiceTest extends TestCase
{
    protected PricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricing = new PricingService();
    }

    #[Test]
    public function weekdays_and_weekends_have_correct_rates_and_are_inclusive(): void
    {
        // Monday to Sunday (7 days)
        $from = Carbon::parse('2025-03-03'); // Monday
        $to   = Carbon::parse('2025-03-09'); // Sunday

        $result = $this->pricing->calculate($from, $to);

        $this->assertEquals(7, $result['days']); // inclusive
        $this->assertEquals(2, collect($result['daily'])->where('is_weekend', true)->count());
        $this->assertEquals(5, collect($result['daily'])->where('is_weekend', false)->count());
    }

    #[Test]
    public function summer_months_add_surcharge(): void
    {
        $from = Carbon::parse('2025-07-01');
        $to   = Carbon::parse('2025-07-03');

        $result = $this->pricing->calculate($from, $to);

        // Each day in July adds +300 cents
        foreach ($result['daily'] as $day) {
            $this->assertTrue($day['is_summer']);
            $this->assertGreaterThanOrEqual(1500, $day['price_cents']); // min £15
        }

        $this->assertEquals(3, $result['days']);
    }

    #[Test]
    public function single_day_booking_is_counted_as_one_full_day(): void
    {
        $from = Carbon::parse('2025-05-10');
        $to   = Carbon::parse('2025-05-10');

        $result = $this->pricing->calculate($from, $to);

        $this->assertEquals(1, $result['days']);
        $this->assertCount(1, $result['daily']);
        $this->assertEquals($result['daily'][0]['price_cents'], $result['total_price_cents']);
    }
}
