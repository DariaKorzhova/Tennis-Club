<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserHasActiveSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin() || $user->isTrainer()) {
            return $next($request);
        }

        $subscription = $user->activeSubscription;

        if (!$subscription || !$subscription->isUsable()) {
            return redirect()->route('subscriptions.choose')
                ->with('error', 'сначала нужно оформить активный абонемент.');
        }

        return $next($request);
    }
}