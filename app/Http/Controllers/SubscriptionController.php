<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
class SubscriptionController extends Controller
{
    private const CHECKOUT_SESSION_KEY = 'subscription_checkout';

    private const INSTALLMENT_CHECKOUT_SESSION_KEY = 'subscription_installment_checkout';

    private const CHECKOUT_TTL_SECONDS = 900;

    public function choose()
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('full_price')
            ->get();

        $user = Auth::user();
        $subscription = $user ? $user->activeSubscription : null;
        $savedCardForModal = $user ? $user->savedCardForPaymentModal() : null;

        return view('subscriptions.choose', compact('plans', 'subscription', 'savedCardForModal'));
    }

    public function paymentInit(Request $request)
    {
        $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'payment_mode' => ['required', 'in:one_time,installment'],
            'auto_renew' => ['nullable', 'boolean'],
            'remember_card' => ['nullable', 'boolean'],
            'card_number' => ['required', 'string', 'regex:/^[\d\s]{13,23}$/'],
            'card_expiry' => ['required', 'string'],
            'card_cvv' => ['required', 'string', 'regex:/^\d{3,4}$/'],
        ]);

        $user = Auth::user();
        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $paymentMode = $request->payment_mode;

        if ($paymentMode === 'installment' && !$plan->allows_installment) {
            return back()->with('error', 'для этого абонемента рассрочка недоступна.')->withInput();
        }

        $digits = preg_replace('/\D/', '', $request->card_number);
        if (strlen($digits) < 13 || strlen($digits) > 19) {
            return back()->withErrors(['card_number' => 'укажите корректный номер карты.'])->withInput();
        }

        $expiryError = $this->validateCardExpiry($request->card_expiry);
        if ($expiryError !== null) {
            return back()->withErrors(['card_expiry' => $expiryError])->withInput();
        }

        $autoRenew = $plan->auto_renew_available && $request->boolean('auto_renew');

        $payload = [
            'plan_id' => (int) $plan->id,
            'payment_mode' => $paymentMode,
            'auto_renew' => $autoRenew,
            'remember_card' => $request->boolean('remember_card'),
            'card_number_digits' => $digits,
            'card_expiry' => $request->card_expiry,
            'created_at' => time(),
        ];

        $request->session()->put(self::CHECKOUT_SESSION_KEY, $payload);

        return redirect()->route('subscriptions.payment.verify');
    }

    public function paymentVerify(Request $request)
    {
        if (!$this->checkoutPayload($request)) {
            return redirect()->route('subscriptions.choose')
                ->with('error', 'сессия оплаты истекла или не найдена. выберите абонемент снова.');
        }

        return view('subscriptions.payment-verify', [
            'paymentCompleteAction' => route('subscriptions.payment.complete'),
            'verifyBackHref' => route('subscriptions.choose'),
            'verifyBackLabel' => 'вернуться к выбору абонемента',
        ]);
    }

    public function index()
    {
        $subscriptions = Auth::user()
            ->subscriptions()
            ->with('plan')
            ->orderByDesc('id')
            ->get();

        return view('subscriptions.index', compact('subscriptions'));
    }

    public function installmentPaymentInit(Request $request)
    {
        $request->validate([
            'subscription_id' => ['required', 'integer', 'exists:user_subscriptions,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'remember_card' => ['nullable', 'boolean'],
            'card_number' => ['required', 'string', 'regex:/^[\d\s]{13,23}$/'],
            'card_expiry' => ['required', 'string'],
            'card_cvv' => ['required', 'string', 'regex:/^\d{3,4}$/'],
        ]);

        $user = Auth::user();
        /** @var UserSubscription $subscription */
        $subscription = UserSubscription::query()
            ->where('user_id', $user->id)
            ->whereKey($request->subscription_id)
            ->with('plan')
            ->first();

        if (!$subscription) {
            return redirect()->route('account')->with('error', 'абонемент не найден.');
        }

        if ($subscription->payment_mode !== 'installment' || $subscription->status !== 'active') {
            return redirect()->route('account')->with('error', 'для этого абонемента нельзя внести платёж таким способом.');
        }

        $remaining = $subscription->remainingPlanAmount();
        $amount = (int) $request->amount;
        if ($amount > $remaining) {
            return back()->withErrors(['amount' => 'сумма не может превышать остаток по договору (' . $remaining . ' ₽).'])->withInput();
        }

        $digits = preg_replace('/\D/', '', $request->card_number);
        if (strlen($digits) < 13 || strlen($digits) > 19) {
            return back()->withErrors(['card_number' => 'укажите корректный номер карты.'])->withInput();
        }

        $expiryError = $this->validateCardExpiry($request->card_expiry);
        if ($expiryError !== null) {
            return back()->withErrors(['card_expiry' => $expiryError])->withInput();
        }

        $payload = [
            'subscription_id' => (int) $subscription->id,
            'amount' => $amount,
            'remember_card' => $request->boolean('remember_card'),
            'card_number_digits' => $digits,
            'card_expiry' => $request->card_expiry,
            'created_at' => time(),
        ];

        $request->session()->put(self::INSTALLMENT_CHECKOUT_SESSION_KEY, $payload);

        return redirect()->route('subscriptions.installment-payment.verify');
    }

    public function installmentPaymentVerify(Request $request)
    {
        if (!$this->installmentCheckoutPayload($request)) {
            return redirect()->route('account')
                ->with('error', 'сессия оплаты истекла. попробуйте снова.');
        }

        return view('subscriptions.payment-verify', [
            'paymentCompleteAction' => route('subscriptions.installment-payment.complete'),
            'verifyBackHref' => route('account') . '#account-subscription',
            'verifyBackLabel' => 'вернуться в личный кабинет',
        ]);
    }

    public function installmentPaymentComplete(Request $request)
    {
        $request->validate([
            'sms_code' => ['required', 'string', 'regex:/^\d{4}$/'],
        ]);

        $payload = $this->installmentCheckoutPayload($request);
        if (!$payload) {
            return redirect()->route('account')
                ->with('error', 'сессия оплаты истекла.');
        }

        $user = Auth::user();
        /** @var UserSubscription $subscription */
        $subscription = UserSubscription::query()
            ->where('user_id', $user->id)
            ->whereKey($payload['subscription_id'])
            ->first();

        if (!$subscription || $subscription->payment_mode !== 'installment' || $subscription->status !== 'active') {
            $request->session()->forget(self::INSTALLMENT_CHECKOUT_SESSION_KEY);

            return redirect()->route('account')->with('error', 'абонемент недоступен для оплаты.');
        }

        $remaining = $subscription->remainingPlanAmount();
        $amount = (int) $payload['amount'];
        if ($amount < 1 || $amount > $remaining) {
            $request->session()->forget(self::INSTALLMENT_CHECKOUT_SESSION_KEY);

            return redirect()->route('account')->with('error', 'некорректная сумма платежа.');
        }

        $today = Carbon::today();
        SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'payment_type' => 'installment',
            'status' => 'paid',
            'due_date' => $today,
            'paid_at' => now(),
            'transaction_id' => 'inst_' . uniqid('', true),
        ]);

        if ($subscription->next_payment_date) {
            $subscription->next_payment_date = $subscription->next_payment_date->copy()->addMonth();
        }
        $subscription->save();

        $this->syncSavedCardAfterPayment($user, $payload);

        $request->session()->forget(self::INSTALLMENT_CHECKOUT_SESSION_KEY);

        return redirect()->to(route('account') . '#account-subscription')->with('success', 'платёж успешно зачислен.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function installmentCheckoutPayload(Request $request): ?array
    {
        $payload = $request->session()->get(self::INSTALLMENT_CHECKOUT_SESSION_KEY);
        if (!is_array($payload) || empty($payload['subscription_id']) || empty($payload['amount'])) {
            return null;
        }
        $created = (int) ($payload['created_at'] ?? 0);
        if ($created < 1 || (time() - $created) > self::CHECKOUT_TTL_SECONDS) {
            $request->session()->forget(self::INSTALLMENT_CHECKOUT_SESSION_KEY);

            return null;
        }

        return $payload;
    }

    public function paymentComplete(Request $request)
    {
        $request->validate([
            'sms_code' => ['required', 'string', 'regex:/^\d{4}$/'],
        ]);

        $payload = $this->checkoutPayload($request);
        if (!$payload) {
            return redirect()->route('subscriptions.choose')
                ->with('error', 'сессия оплаты истекла. оформите абонемент заново.');
        }

        $user = Auth::user();
        $this->activateSubscription($user, $payload);
        $this->syncSavedCardAfterPayment($user, $payload);

        $request->session()->forget(self::CHECKOUT_SESSION_KEY);

        return redirect()->route('account')->with('success', 'абонемент успешно оформлен.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function checkoutPayload(Request $request): ?array
    {
        $payload = $request->session()->get(self::CHECKOUT_SESSION_KEY);
        if (!is_array($payload) || empty($payload['plan_id']) || empty($payload['payment_mode'])) {
            return null;
        }
        $created = (int) ($payload['created_at'] ?? 0);
        if ($created < 1 || (time() - $created) > self::CHECKOUT_TTL_SECONDS) {
            $request->session()->forget(self::CHECKOUT_SESSION_KEY);

            return null;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function activateSubscription($user, array $payload): void
    {
        $plan = SubscriptionPlan::findOrFail($payload['plan_id']);
        $paymentMode = $payload['payment_mode'];

        if (!in_array($paymentMode, ['one_time', 'installment'], true)) {
            abort(400);
        }

        if ($paymentMode === 'installment' && !$plan->allows_installment) {
            abort(400);
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
            'next_payment_date' => $paymentMode === 'installment' ? $startDate->copy()->addMonth() : null,
            'visits_left' => $plan->visit_limit,
            'auto_renew' => (bool) ($payload['auto_renew'] ?? false),
            'freeze_days_left' => (int) $plan->freeze_days_per_year,
        ]);

        if ($paymentMode === 'one_time') {
            $amount = (int) $plan->full_price;
        } else {
            $amount = $this->installmentFirstPaymentAmount($plan);
        }

        $paymentType = $paymentMode === 'installment' ? 'installment' : 'initial';

        SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'payment_type' => $paymentType,
            'status' => 'paid',
            'due_date' => $startDate,
            'paid_at' => now(),
            'transaction_id' => 'card_' . uniqid('', true),
        ]);
    }

    private function installmentFirstPaymentAmount(SubscriptionPlan $plan): int
    {
        $months = max(1, (int) $plan->duration_months);
        $full = (int) $plan->full_price;

        return intdiv($full, $months) + ($full % $months);
    }

    private function validateCardExpiry(?string $expiry): ?string
    {
        if ($expiry === null || $expiry === '') {
            return 'укажите срок действия карты.';
        }

        if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry, $m)) {
            return 'неверный формат срока. используйте ММ/ГГ (например, 08/27).';
        }

        $month = (int) $m[1];
        $year = 2000 + (int) $m[2];
        $currentYear = (int) now()->format('Y');
        if ($year < $currentYear - 1 || $year > $currentYear + 25) {
            return 'укажите корректный год на карте.';
        }

        $lastValidDay = Carbon::createFromDate($year, $month, 1)->endOfMonth()->startOfDay();
        if ($lastValidDay->lt(Carbon::today()->startOfDay())) {
            return 'срок действия карты истёк.';
        }

        return null;
    }

    /**
     * @param  \App\Models\User  $user
     * @param  array<string, mixed>  $payload
     */
    private function syncSavedCardAfterPayment($user, array $payload): void
    {
        if (empty($payload['remember_card'])) {
            return;
        }

        $digits = (string) ($payload['card_number_digits'] ?? '');
        $expiry = (string) ($payload['card_expiry'] ?? '');
        if (strlen($digits) >= 13 && preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
            $user->saved_card_number = Crypt::encryptString($digits);
            $user->saved_card_expiry = Crypt::encryptString($expiry);
            $user->save();
        }
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        $subscriptionId = $request->query('subscription_id');

        if ($subscriptionId) {
            $subscription = $user->subscriptions()
                ->whereKey((int) $subscriptionId)
                ->with(['plan', 'payments'])
                ->first();

            if (!$subscription) {
                abort(404);
            }
        } else {
            $subscription = $user->activeSubscription()
                ->with(['plan', 'payments'])
                ->first();
        }

        return view('subscriptions.history', compact('subscription'));
    }
}
