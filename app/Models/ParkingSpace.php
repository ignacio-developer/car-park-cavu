<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParkingSpace extends Model
{
    protected $fillable = ['space'];

    public function booking(): HasMany
    {
        return $this->hasMany(Booking::class, 'space_id');
    }

    public function days(): HasMany
    {
        return $this->hasMany(BookingDay::class, 'space_id');
    }
    
}
