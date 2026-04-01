<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function choose()
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('full_price')
            ->get();

        $user = Auth::user();
        $subscription = $user ? $user->activeSubscription : null;

        return view('subscriptions.choose', compact('plans', 'subscription'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'payment_mode' => ['required', 'in:one_time,monthly,installment'],
            'auto_renew' => ['nullable', 'boolean'],
        ]);

        $user = Auth::user();
        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $paymentMode = $request->payment_mode;

        if ($paymentMode === 'monthly' && !$plan->allows_monthly_payment) {
            return back()->with('error', 'Для этого абонемента помесячная оплата недоступна.');
        }

        if ($paymentMode === 'installment' && !$plan->allows_installment) {
            return back()->with('error', 'Для этого абонемента рассрочка недоступна.');
        }

        $existingActive = $user->subscriptions()
            ->whereIn('status', ['pending', 'active', 'payment_overdue'])
            ->get();

        foreach ($existingActive as $oldSubscription) {
            $oldSubscription->status = 'cancelled';
            $oldSubscription->save();
        }

        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addMonths($plan->duration_months)->subDay();

        $subscription = UserSubscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'payment_mode' => $paymentMode,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'next_payment_date' => in_array($paymentMode, ['monthly', 'installment']) ? $startDate->copy()->addMonth() : null,
            'visits_left' => $plan->visit_limit,
            'auto_renew' => (bool) $request->boolean('auto_renew'),
            'freeze_days_left' => (int) $plan->freeze_days_per_year,
        ]);

        $amount = $paymentMode === 'one_time'
            ? (int) $plan->full_price
            : (int) $plan->monthly_price;

        $paymentType = $paymentMode === 'installment' ? 'installment' : ($paymentMode === 'monthly' ? 'monthly' : 'initial');

        SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'payment_type' => $paymentType,
            'status' => 'paid',
            'due_date' => $startDate,
            'paid_at' => now(),
            'transaction_id' => 'manual_' . uniqid(),
        ]);

        return redirect()->route('account')->with('success', 'Абонемент успешно оформлен.');
    }

    public function history()
{
    $user = Auth::user();

    $subscription = $user->activeSubscription()
        ->with(['plan', 'payments'])
        ->first();

    return view('subscriptions.history', compact('subscription'));
}
}