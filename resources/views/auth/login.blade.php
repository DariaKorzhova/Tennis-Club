@extends('layouts.app')

@section('title', 'вход')

@section('content')
<div class="auth-container">
    <div class="auth-header">
        <h3>вход в систему</h3>
    </div>

    @if (session('status'))
        <div class="auth-notification success">
            {{ session('status') }}
        </div>
    @endif

    @if (session('success'))
        <div class="auth-notification success">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login.post') }}">
        @csrf

        <div class="form-group @error('email') has-error @enderror">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-input" id="email" name="email"
                   value="{{ old('email') }}" required autofocus>
            @error('email')
            <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group @error('password') has-error @enderror">
    <label for="password" class="form-label">пароль</label>
    <input type="password" class="form-input" id="password" name="password" required>
    @error('password')
    <span class="error-message">{{ $message }}</span>
    @enderror
</div>

<div class="form-checkbox" style="margin-bottom: 16px;">
    <input type="checkbox" class="checkbox-input" id="show_password">
    <label class="checkbox-label" for="show_password">показать пароль</label>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('show_password');
    const password = document.getElementById('password');

    if (toggle && password) {
        toggle.addEventListener('change', function () {
            password.type = toggle.checked ? 'text' : 'password';
        });
    }
});
</script>

        <div class="form-checkbox">
            <input type="checkbox" class="checkbox-input" id="remember" name="remember">
            <label class="checkbox-label" for="remember">запомнить меня</label>
        </div>

        <div class="auth-links" style="margin:10px 0px;">
            <a href="{{ route('password.request') }}" class="auth-link">забыли пароль?</a>
        </div>

        <button type="submit" class="form-button">войти</button>
    </form>

    <div class="auth-links">
        <p>нет аккаунта? <a href="{{ route('register') }}" class="auth-link">зарегистрироваться</a></p>
    </div>
</div>
@endsection
