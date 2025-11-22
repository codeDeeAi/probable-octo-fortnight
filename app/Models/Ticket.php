<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'type',
        'price',
        'quantity',
    ];

    /**
     * Get the event that owns the Ticket
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_id', 'id');
    }

    /**
     * Get all of the bookings for the Ticket
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'ticket_id', 'id');
    }
}
