@extends('layouts.app')

@section('title', 'подтверждение входа')

@section('content')
<div class="auth-container">
    <div class="auth-header">
        <h3>подтверждение входа</h3>
    </div>

    @if (session('status'))
        <div class="auth-notification success">
            {{ session('status') }}
        </div>
    @endif

    <p class="muted" style="margin-bottom:12px;">
        мы отправили 6-значный код на вашу почту. введите его ниже.
    </p>

    <form method="POST" action="{{ route('2fa.verify') }}">
        @csrf

        <div class="form-group @error('code') has-error @enderror">
            <label for="code" class="form-label">код</label>
            <input type="text" class="form-input" id="code" name="code" maxlength="6" required autofocus>
            @error('code')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="form-button">подтвердить</button>
    </form>

    <form method="POST" action="{{ route('2fa.resend') }}" style="margin-top:12px;">
        @csrf
        <button type="submit" class="btn-secondary form-button" style="width:100%;">отправить код ещё раз</button>
    </form>

    <div class="auth-links" style="margin-top:12px;">
        <a class="auth-link" href="{{ route('login') }}">вернуться ко входу</a>
    </div>
</div>
@endsection
