<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

// Create a new booking.
Route::post('/bookings', [BookingController::class, 'store']);

// Check availability, spaces available per day.
Route::get('/availability', [BookingController::class, 'checkAvailability']);

// Get a price quote for a date range.
Route::get('/pricing', [BookingController::class, 'quote']);

// Amend an existing booking.
Route::patch('/bookings/{booking}', [BookingController::class, 'update']);

// Cancel a booking, inactive and remove days.
Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);