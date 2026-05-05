@extends('layouts.app')

@section('title', 'регистрация')

@section('content')
<div class="auth-container">
    <div class="auth-header">
        <h3>регистрация</h3>
    </div>

    @if ($errors->any())
        <div class="auth-notification error">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.post') }}">
        @csrf

        <div class="form-group @error('first_name') has-error @enderror">
            <label for="first_name" class="form-label">имя</label>
            <input
                type="text"
                class="form-input"
                id="first_name"
                name="first_name"
                value="{{ old('first_name') }}"
                required
                autofocus
            >
            @error('first_name')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group @error('last_name') has-error @enderror">
            <label for="last_name" class="form-label">фамилия</label>
            <input
                type="text"
                class="form-input"
                id="last_name"
                name="last_name"
                value="{{ old('last_name') }}"
                required
            >
            @error('last_name')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group @error('email') has-error @enderror">
            <label for="email" class="form-label">Email</label>
            <input
                type="email"
                class="form-input"
                id="email"
                name="email"
                value="{{ old('email') }}"
                required
            >
            @error('email')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group @error('birth_date') has-error @enderror">
            <label for="birth_date" class="form-label">дата рождения</label>
            <input
                type="text"
                class="form-input"
                id="birth_date"
                name="birth_date"
                value="{{ old('birth_date') }}"
                placeholder="18.08.2006"
                maxlength="10"
                required
            >
            @error('birth_date')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group @error('password') has-error @enderror">
            <label for="password" class="form-label">пароль</label>
            <input
                type="password"
                class="form-input password-toggle-field"
                id="password"
                name="password"
                minlength="8"
                required
            >
            @error('password')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group @error('password_confirmation') has-error @enderror">
            <label for="password_confirmation" class="form-label">подтвердите пароль</label>
            <input
                type="password"
                class="form-input password-toggle-field"
                id="password_confirmation"
                name="password_confirmation"
                minlength="8"
                required
            >
            @error('password_confirmation')
                <span class="error-message">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-checkbox" style="margin-bottom: 16px;">
            <input type="checkbox" class="checkbox-input" id="show_passwords">
            <label class="checkbox-label" for="show_passwords">показать пароль</label>
        </div>

        <button type="submit" class="form-button">зарегистрироваться</button>
    </form>

    <div class="auth-links">
        <p>уже есть аккаунт? <a href="{{ route('login') }}" class="auth-link">войти</a></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const nameFields = ['first_name', 'last_name'];

    nameFields.forEach(function (id) {
        const input = document.getElementById(id);

        if (!input) return;

        input.addEventListener('input', function () {
            let value = this.value
                .replace(/[^А-Яа-яЁё]/g, '')
                .toLowerCase();

            if (value.length > 0) {
                value = value.charAt(0).toUpperCase() + value.slice(1);
            }

            this.value = value;
        });
    });

    const birthDateInput = document.getElementById('birth_date');

    if (birthDateInput) {
        birthDateInput.addEventListener('input', function () {
            let value = this.value.replace(/\D/g, '').slice(0, 8);

            if (value.length >= 5) {
                value = value.slice(0, 2) + '.' + value.slice(2, 4) + '.' + value.slice(4);
            } else if (value.length >= 3) {
                value = value.slice(0, 2) + '.' + value.slice(2);
            }

            this.value = value;
        });
    }

    const toggle = document.getElementById('show_passwords');
    const passwordFields = document.querySelectorAll('.password-toggle-field');

    if (toggle) {
        toggle.addEventListener('change', function () {
            passwordFields.forEach(function (field) {
                field.type = toggle.checked ? 'text' : 'password';
            });
        });
    }
});
</script>
@endsection