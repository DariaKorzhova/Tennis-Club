<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingBooking extends Model
{
    protected $fillable = [
        'training_id',
        'account_user_id',
        'bookable_type',
        'bookable_id',
        'subscription_id',
        'status',
        'price',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function accountUser()
    {
        return $this->belongsTo(User::class, 'account_user_id');
    }

    public function subscription()
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id');
    }

    public function child()
    {
        return $this->belongsTo(Child::class, 'bookable_id');
    }

    public function userPerson()
    {
        return $this->belongsTo(User::class, 'bookable_id');
    }

    public function getBookableNameAttribute()
    {
        if ($this->bookable_type === 'child' && $this->child) {
            return $this->child->full_name;
        }

        if ($this->bookable_type === 'user' && $this->userPerson) {
            return $this->userPerson->full_name;
        }

        return '—';
    }
}