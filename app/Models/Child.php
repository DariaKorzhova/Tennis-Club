<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Child extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'birth_date',
        'gender',
        'level',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'owner_id')
            ->where('owner_type', 'child');
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class, 'owner_id')
            ->where('owner_type', 'child')
            ->where('status', 'active')
            ->latestOfMany();
    }

    public function bookings()
    {
        return $this->hasMany(TrainingBooking::class, 'bookable_id')
            ->where('bookable_type', 'child');
    }

    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getAgeAttribute()
    {
        return $this->birth_date ? Carbon::parse($this->birth_date)->age : null;
    }
}