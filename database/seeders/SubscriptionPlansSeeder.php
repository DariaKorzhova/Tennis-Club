<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlansSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'name' => 'Годовой',
                'code' => 'YEAR_12_MONTHLY',
                'type' => 'yearly',
                'duration_months' => 12,
                'monthly_price' => 4500,
                'full_price' => 54000,
                'visit_limit' => null,
                'allows_installment' => true,
                'allows_monthly_payment' => true,
                'auto_renew_available' => false,
                'freeze_days_per_year' => 30,
                'includes_court_booking' => true,
                'includes_trainings' => true,
                'description' => 'Годовой абонемент с помесячной оплатой и возможностью заморозки.',
                'is_active' => true,
            ],
            [
                'name' => 'Свободное посещение',
                'code' => 'FREE_VISIT_ONE_TIME',
                'type' => 'free_visit',
                'duration_months' => 1,
                'monthly_price' => 0,
                'full_price' => 6000,
                'visit_limit' => null,
                'allows_installment' => false,
                'allows_monthly_payment' => false,
                'auto_renew_available' => false,
                'freeze_days_per_year' => 0,
                'includes_court_booking' => true,
                'includes_trainings' => true,
                'description' => 'Безлимитное посещение на 30 дней с оплатой сразу.',
                'is_active' => true,
            ],
            [
                'name' => 'Свободное посещение + ежемесячная оплата',
                'code' => 'FREE_VISIT_MONTHLY',
                'type' => 'free_visit',
                'duration_months' => 1,
                'monthly_price' => 5500,
                'full_price' => 5500,
                'visit_limit' => null,
                'allows_installment' => false,
                'allows_monthly_payment' => true,
                'auto_renew_available' => true,
                'freeze_days_per_year' => 0,
                'includes_court_booking' => true,
                'includes_trainings' => true,
                'description' => 'Свободное посещение с ежемесячной оплатой и автопродлением.',
                'is_active' => true,
            ],
            [
                'name' => 'Дневной',
                'code' => 'DAYTIME_MONTHLY',
                'type' => 'daytime',
                'duration_months' => 1,
                'monthly_price' => 3500,
                'full_price' => 3500,
                'visit_limit' => null,
                'allows_installment' => false,
                'allows_monthly_payment' => true,
                'auto_renew_available' => true,
                'freeze_days_per_year' => 0,
                'includes_court_booking' => true,
                'includes_trainings' => true,
                'description' => 'Посещение в будни до 17:00.',
                'is_active' => true,
            ],
            [
                'name' => '8 посещений в месяц',
                'code' => 'LIMITED_8',
                'type' => 'limited',
                'duration_months' => 1,
                'monthly_price' => 3000,
                'full_price' => 3000,
                'visit_limit' => 8,
                'allows_installment' => false,
                'allows_monthly_payment' => true,
                'auto_renew_available' => true,
                'freeze_days_per_year' => 0,
                'includes_court_booking' => true,
                'includes_trainings' => true,
                'description' => 'Лимитный тариф на 8 посещений в месяц.',
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['code' => $plan['code']],
                $plan
            );
        }
    }
}