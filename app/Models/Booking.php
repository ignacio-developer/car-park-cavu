<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_CANCELLED = 'inactive';


    // Allow mass assignment/insertion.
    protected $fillable = ['space_id', 'start_at', 'end_at', 'reg_plate', 'total_price_cents', 'status'];

    // Cast values to be readable/usable, Carbon date type and integer type.
    protected $casts = ['start_at' => 'datetime', 'end_at' => 'datetime', 'total_price_cents' => 'integer'];

    public function space(): BelongsTo 
    {
        return $this->belongsTo(ParkingSpace::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(BookingDay::class);
    }

    /* Scopes */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

}
