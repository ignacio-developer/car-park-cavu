<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use App\Services\BookingService;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use Carbon\Carbon;

class BookingController extends Controller
{

    protected BookingService $bookingService;
    protected AvailabilityService $availabilityService;

    public function __construct(BookingService $bookingService, AvailabilityService $availabilityService, PricingService $pricingService)
    {
        $this->bookingService = $bookingService;
        $this->availabilityService = $availabilityService;
        $this->pricingService = $pricingService;
    }

    public function checkAvailability(Request $request)
    {
        $data = $request->validate([
            'start_at' => 'required|date_format:Y-m-d H:i',
            'end_at'   => 'required|date_format:Y-m-d H:i|after_or_equal:start_at',
        ]);

        return response()->json(
            $this->availabilityService->checkAvailability($data['start_at'], $data['end_at'])
        );
    }

    public function quote(Request $request)
    {
        $data = $request->validate([
            'start_at' => 'required|date_format:Y-m-d H:i',
            'end_at'   => 'required|date_format:Y-m-d H:i|after_or_equal:start_at',
        ]);

        $from = Carbon::parse($data['start_at'])->startOfDay();
        $to   = Carbon::parse($data['end_at'])->startOfDay();

        return response()->json($this->pricingService->calculate($from, $to));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        //Checking if request is coming as expected.
        //return response()->json($request->all());

        //Validate that we recieve required params + rules.
        $data = $request->validate([
            'reg_plate' => 'required|string|max:10',
            'start_at'  => 'required|date_format:Y-m-d H:i',
            'end_at'    => 'required|date_format:Y-m-d H:i|after_or_equal:start_at',
        ]);

        // This will hit BookingService::create($data) and return 201 (success),
        return response()->json($this->bookingService->create($data), 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking)
    {
        // allow partial updates
        $data = $request->validate([
            'reg_plate' => 'sometimes|string|max:10',
            'start_at'  => 'sometimes|date_format:Y-m-d H:i',
            'end_at'    => 'sometimes|date_format:Y-m-d H:i|after_or_equal:start_at',
        ]);

        // delegate to service (amend handles availability + price + days)
        return response()->json($this->bookingService->amend($booking, $data));
    }

    /**
     * Actually removes the booking_days and change the booking to 'inactive'.
     */
    public function destroy(Booking $booking)
    {
        $this->bookingService->cancel($booking);
        //if deleted -> show message, if not found, 404 default error (to customize create error handler).
        return response()->json(['message' => 'Your booking has been successfully cancelled.'], 200);
    }

}


