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
        'pending' => 'ожидает оплаты',
        'active' => 'активен',
        'paused' => 'приостановлен',
        'expired' => 'истёк',
        'cancelled' => 'отменён',
        'payment_overdue' => 'просрочен платёж',
    ];

    return $map[$this->status] ?? $this->status;
}

public function getPaymentModeLabelAttribute(): string
{
    $map = [
        'one_time' => 'единоразово',
        'monthly' => 'раз в месяц',
        'installment' => 'рассрочка',
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

    public function paidTotalAmount(): int
    {
        return (int) $this->payments()->where('status', 'paid')->sum('amount');
    }

    public function remainingPlanAmount(): int
    {
        if (!$this->plan) {
            return 0;
        }

        return max(0, (int) $this->plan->full_price - $this->paidTotalAmount());
    }

    public function installmentMonthsCount(): int
    {
        return $this->plan ? max(1, (int) $this->plan->duration_months) : 1;
    }

    public function installmentFullPrice(): int
    {
        return $this->plan ? (int) $this->plan->full_price : 0;
    }

    /** Базовая сумма месяца без остатка от деления (после первого платежа). */
    public function installmentBaseMonthlyAmount(): int
    {
        $m = $this->installmentMonthsCount();
        $full = $this->installmentFullPrice();

        return intdiv($full, $m);
    }

    /** Первый платёж в рассрочке (с учётом остатка от деления). */
    public function installmentFirstScheduledAmount(): int
    {
        $m = $this->installmentMonthsCount();
        $full = $this->installmentFullPrice();

        return intdiv($full, $m) + ($full % $m);
    }

    /**
     * Рекомендуемая сумма следующего взноса по рассрочке.
     */
    public function suggestedInstallmentNextPaymentAmount(): ?int
    {
        if ($this->payment_mode !== 'installment' || !$this->plan) {
            return null;
        }

        $remaining = $this->remainingPlanAmount();
        if ($remaining <= 0) {
            return null;
        }

        $paidCount = (int) $this->payments()->where('status', 'paid')->count();
        $months = $this->installmentMonthsCount();
        $leftMonths = max(1, $months - $paidCount);

        return (int) max(1, min($remaining, (int) ceil($remaining / $leftMonths)));
    }
}