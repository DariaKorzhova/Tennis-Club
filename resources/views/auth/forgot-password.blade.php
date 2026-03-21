@extends('layouts.app')

@section('title', 'Восстановление пароля')

@section('content')
<div class="auth-container">
    <div class="auth-header">
        <h3>Восстановление пароля</h3>
    </div>

    @if (session('status'))
        <div class="auth-notification success">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group @error('email') has-error @enderror">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-input" id="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="form-button">Отправить ссылку</button>
    </form>

    <div class="auth-links">
        <p><a href="{{ route('login') }}" class="auth-link">Назад ко входу</a></p>
    </div>
</div>
@endsection
