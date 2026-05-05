@extends('layouts.app')

@section('title', 'подтверждение оплаты')

@section('content')
<div class="container">
    <div class="subscription-page subscription-page--narrow">
        <h1>код из смс</h1>
        <p class="muted subscription-verify-lead">
            на номер телефона владельца карты отправлен код из 4 цифр. введите его, чтобы завершить оплату абонемента.
        </p>

        <form method="POST" action="{{ $paymentCompleteAction ?? route('subscriptions.payment.complete') }}" class="subscription-verify-form">
            @csrf
            <label class="form-label" for="sms_code">код</label>
            <input type="text"
                   name="sms_code"
                   id="sms_code"
                   class="form-input subscription-verify-code"
                   inputmode="numeric"
                   autocomplete="one-time-code"
                   maxlength="4"
                   pattern="\d{4}"
                   placeholder="0000"
                   required
                   autofocus>

            @error('sms_code')
                <div class="form-error">{{ $message }}</div>
            @enderror

            <button type="submit" class="form-button" style="margin-top:16px;">подтвердить</button>
        </form>

        <a href="{{ $verifyBackHref ?? route('subscriptions.choose') }}" class="form-button form-button--secondary subscription-verify-back">
            {{ $verifyBackLabel ?? 'вернуться к выбору абонемента' }}
        </a>
    </div>
</div>
@endsection
