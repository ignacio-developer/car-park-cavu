<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDay extends Model
{
    protected $fillable = ['booking_id', 'space_id', 'date'];

    protected $casts = ['date' => 'date'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(ParkingSpace::class);
    }

    public function scopeBetween($query, string $from, string $to)
    {
        return $query->whereBetween('date', [$from, $to]);
    }
}
