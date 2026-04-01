<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'duration_months',
        'monthly_price',
        'full_price',
        'visit_limit',
        'allows_installment',
        'allows_monthly_payment',
        'auto_renew_available',
        'freeze_days_per_year',
        'includes_court_booking',
        'includes_trainings',
        'description',
        'is_active',
    ];

    protected $casts = [
        'allows_installment' => 'boolean',
        'allows_monthly_payment' => 'boolean',
        'auto_renew_available' => 'boolean',
        'includes_court_booking' => 'boolean',
        'includes_trainings' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'plan_id');
    }
}