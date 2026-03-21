@extends('layouts.app')

@section('title', 'Сброс пароля')

@section('content')
<div class="auth-container">
    <div class="auth-header">
        <h3>Сброс пароля</h3>
    </div>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="form-group @error('password') has-error @enderror">
            <label for="password" class="form-label">Новый пароль</label>
            <input type="password" class="form-input" id="password" name="password" required>
            @error('password')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="password_confirmation" class="form-label">Повторите пароль</label>
            <input type="password" class="form-input" id="password_confirmation" name="password_confirmation" required>
        </div>

        <button type="submit" class="form-button">Сохранить пароль</button>
    </form>

    <div class="auth-links">
        <p><a href="{{ route('login') }}" class="auth-link">Назад ко входу</a></p>
    </div>
</div>
@endsection
