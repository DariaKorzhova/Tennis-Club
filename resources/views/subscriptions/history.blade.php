@extends('layouts.app')

@section('title', 'История платежей')

@section('content')
<div class="container">
    <div class="account-layout-edit">    

        <div class="account-content">
            <div class="account-edit-shell">
            <a href="{{ route('account') }}" class="account-back-btn">← Назад</a>
            <div class="account-edit-card">

                <h2 class="account-edit-title">История платежей</h2>

                @if(!$subscription)
                    <div class="muted">Активного абонемента нет.</div>
                    <a href="{{ route('subscriptions.choose') }}" class="account-edit-btn">Оформить абонемент</a>
                @else
                    <div class="account-card" style="margin-bottom: 20px;">
                        <p><strong>Абонемент:</strong> {{ $subscription->plan->name }}</p>
                        <p><strong>Статус:</strong> {{ $subscription->status }}</p>
                        <p><strong>Способ оплаты:</strong> {{ $subscription->payment_mode }}</p>
                        <p><strong>Действует до:</strong> {{ optional($subscription->end_date)->format('d.m.Y') }}</p>
                    </div>

                    @if($subscription->payments->isEmpty())
                        <div class="muted">Платежей пока нет.</div>
                    @else
                        <div class="users-table-container">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>Дата платежа</th>
                                        <th>Сумма</th>
                                        <th>Тип</th>
                                        <th>Статус</th>
                                        <th>Срок оплаты</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscription->payments->sortByDesc('created_at') as $payment)
                                        <tr>
                                            <td>{{ $payment->paid_at ? $payment->paid_at->format('d.m.Y H:i') : '—' }}</td>
                                            <td>{{ (int)$payment->amount }} ₽</td>
                                            <td>{{ $payment->payment_type }}</td>
                                            <td>{{ $payment->status }}</td>
                                            <td>{{ $payment->due_date ? $payment->due_date->format('d.m.Y') : '—' }}</td>
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