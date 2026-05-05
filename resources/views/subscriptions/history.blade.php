@extends('layouts.app')

@section('title', 'история платежей')

@section('content')
<div class="container">
    <div class="account-layout-edit">
        <div class="account-content">
            <div class="account-edit-shell">
                <a href="{{ route('subscriptions.index') }}" class="account-back-btn">← все абонементы</a>
                <div class="account-edit-card">
                    <h2 class="account-edit-title">история платежей</h2>

                    @if(!$subscription)
                        <div class="muted">абонемент не найден.</div>
                        <a href="{{ route('subscriptions.choose') }}" class="account-edit-btn" style="margin-top:12px;display:inline-flex;">оформить абонемент</a>
                    @else
                        @php
                            $statusClass = 'account-subscription-badge--pending';
                            if ($subscription->status === 'active') {
                                $statusClass = 'account-subscription-badge--active';
                            } elseif ($subscription->status === 'payment_overdue') {
                                $statusClass = 'account-subscription-badge--overdue';
                            }
                            $paidTotal = $subscription->paidTotalAmount();
                            $planFull = $subscription->installmentFullPrice();
                            $isInstallment = $subscription->payment_mode === 'installment' && $subscription->plan;
                        @endphp

                        <div class="account-card account-subscription-card" style="margin-bottom: 20px;">
                            <div class="account-subscription-actions" style="margin-top:0; margin-bottom:8px;">
                                <span class="account-subscription-badge {{ $statusClass }}">
                                    {{ $subscription->status_label }}
                                </span>
                            </div>

                            <div class="account-subscription-grid">
                                <div class="account-subscription-item">
                                    <span class="k">тариф</span>
                                    <span class="v">{{ optional($subscription->plan)->name ?? '—' }}</span>
                                </div>

                                <div class="account-subscription-item">
                                    <span class="k">действует до</span>
                                    <span class="v">{{ optional($subscription->end_date)->format('d.m.Y') }}</span>
                                </div>
 
                                <div class="account-subscription-item">
                                    <span class="k">оплачено всего</span>
                                    <span class="v">{{ number_format($paidTotal, 0, ',', ' ') }} ₽</span>
                                </div>

                                
                            </div>
                        </div>

                        @if($subscription->payments->isEmpty())
                            <div class="muted">платежей пока нет.</div>
                        @else
                            <div class="users-table-container">
                                <table class="users-table">
                                    <thead>
                                        <tr>
                                            <th>дата платежа</th>
                                            <th>сумма</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($subscription->payments->sortByDesc('created_at') as $payment)
                                            <tr>
                                                <td>{{ $payment->paid_at ? $payment->paid_at->format('d.m.Y H:i') : '—' }}</td>
                                                <td>{{ number_format((int) $payment->amount, 0, ',', ' ') }} ₽</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
