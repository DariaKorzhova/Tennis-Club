@extends('layouts.app')

@section('title', 'Выбор абонемента')

@section('content')
<div class="container">
    <div class="subscription-page">
        <h1>Выберите абонемент</h1>

        <div class="subscription-grid">
            @foreach($plans as $plan)
                <div class="subscription-card">
                    <div class="subscription-card__title">{{ $plan->name }}</div>
                    <div class="subscription-card__desc">{{ $plan->description }}</div>

                    <div class="subscription-card__price">
                        @if($plan->monthly_price > 0)
                            от {{ $plan->monthly_price }} ₽ / мес
                        @else
                            {{ $plan->full_price }} ₽
                        @endif
                    </div>

                    <ul class="subscription-card__list">
                        <li>Срок: {{ $plan->duration_months }} мес.</li>
                        @if($plan->visit_limit)
                            <li>Лимит: {{ $plan->visit_limit }} посещений</li>
                        @else
                            <li>Свободное посещение</li>
                        @endif
                        <li>Заморозка: {{ $plan->freeze_days_per_year }} дней</li>
                    </ul>

                    <form method="POST" action="{{ route('subscriptions.store') }}">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">

                        <label class="form-label">Способ оплаты</label>
                        <select name="payment_mode" class="form-input" required>
                            <option value="one_time">Сразу</option>
                            @if($plan->allows_monthly_payment)
                                <option value="monthly">Раз в месяц</option>
                            @endif
                            @if($plan->allows_installment)
                                <option value="installment">Рассрочка</option>
                            @endif
                        </select>

                        @if($plan->auto_renew_available)
                            <label class="form-checkbox" style="margin-top:12px;">
                                <input type="checkbox" class="checkbox-input" name="auto_renew" value="1">
                                <span class="checkbox-label">Автопродление</span>
                            </label>
                        @endif

                        <button type="submit" class="form-button">Оформить</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection