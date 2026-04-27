<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'payment_mode',
        'start_date',
        'end_date',
        'next_payment_date',
        'visits_left',
        'auto_renew',
        'freeze_days_left',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_payment_date' => 'date',
        'auto_renew' => 'boolean',
    ];

    public function getStatusLabelAttribute(): string
{
    $map = [
        'pending' => 'Ожидает оплаты',
        'active' => 'Активен',
        'paused' => 'Приостановлен',
        'expired' => 'Истёк',
        'cancelled' => 'Отменён',
        'payment_overdue' => 'Просрочен платёж',
    ];

    return $map[$this->status] ?? $this->status;
}

public function getPaymentModeLabelAttribute(): string
{
    $map = [
        'one_time' => 'Единоразово',
        'monthly' => 'Раз в месяц',
        'installment' => 'Рассрочка',
    ];

    return $map[$this->payment_mode] ?? $this->payment_mode;
}

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_id');
    }

    public function isUsable(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->end_date && Carbon::parse($this->end_date)->isPast()) {
            return false;
        }

        return true;
    }

    public function plan()
{
    return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
}

public function ownerUser()
{
    return $this->belongsTo(User::class, 'owner_id');
}

public function ownerChild()
{
    return $this->belongsTo(Child::class, 'owner_id');
}

public function getOwnerNameAttribute()
{
    if ($this->owner_type === 'child' && $this->ownerChild) {
        return $this->ownerChild->full_name;
    }

    if ($this->owner_type === 'user' && $this->ownerUser) {
        return $this->ownerUser->full_name;
    }

    return '—';
}
}