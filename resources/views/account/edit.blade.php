@extends('layouts.app')

@section('title', 'Редактирование аккаунта')

@section('content')
<div class="container">
    <div class="account-edit-shell">
        <div class="account-edit-card">
            <h2 class="account-edit-title">Редактирование аккаунта</h2>

            @if(session('status'))
                <div class="auth-notification success">{{ session('status') }}</div>
            @endif

            @if($errors->any())
                <div class="auth-notification error">
                    <ul style="margin:0; padding-left:20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data" class="account-edit-form">
                @csrf

                <div class="account-edit-grid">
                    <div class="form-group">
                        <label class="form-label" for="first_name">Имя</label>
                        <input
                            type="text"
                            class="form-input"
                            id="first_name"
                            name="first_name"
                            value="{{ old('first_name', $user->first_name) }}"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="last_name">Фамилия</label>
                        <input
                            type="text"
                            class="form-input"
                            id="last_name"
                            name="last_name"
                            value="{{ old('last_name', $user->last_name) }}"
                            required
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="birth_date">Дата рождения</label>
                    <input
                        type="text"
                        class="form-input"
                        id="birth_date"
                        name="birth_date"
                        value="{{ old('birth_date', $user->birth_date ? $user->birth_date->format('d.m.Y') : '') }}"
                        placeholder="18.08.2006"
                        maxlength="10"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="photo">Фото профиля</label>
                    <input
                        type="file"
                        class="form-input"
                        id="photo"
                        name="photo"
                        accept=".jpg,.jpeg,.png,.webp"
                    >
                </div>

                <div class="account-edit-buttons">
                    <button type="submit" class="form-button">Сохранить изменения</button>
                    <a href="{{ route('account') }}" class="account-secondary-btn">Назад</a>
                </div>
            </form>
        </div>

        <div class="account-edit-card">
            <h3 class="account-edit-subtitle">Смена пароля</h3>
            <p class="account-edit-hint">Сначала введите новый пароль, затем получите код подтверждения на почту и подтвердите смену.</p>

            <form method="POST" action="{{ route('account.password.send-code') }}" class="account-password-form">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="new_password">Новый пароль</label>
                    <input
                        type="password"
                        class="form-input password-sync"
                        id="new_password"
                        name="new_password"
                        minlength="8"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_password_confirmation">Подтвердите новый пароль</label>
                    <input
                        type="password"
                        class="form-input password-sync"
                        id="new_password_confirmation"
                        name="new_password_confirmation"
                        minlength="8"
                        required
                    >
                </div>

                <div class="form-checkbox">
                    <input type="checkbox" class="checkbox-input" id="show_passwords">
                    <label class="checkbox-label" for="show_passwords">Показать пароль</label>
                </div>

                <button type="submit" class="form-button">Отправить код на почту</button>
            </form>

            <form method="POST" action="{{ route('account.password.update') }}" class="account-password-confirm-form">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="code">Код подтверждения</label>
                    <input
                        type="text"
                        class="form-input"
                        id="code"
                        name="code"
                        maxlength="6"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        required
                    >
                </div>

                <button type="submit" class="form-button">Подтвердить смену пароля</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    ['first_name', 'last_name'].forEach(function (id) {
        const input = document.getElementById(id);

        if (!input) return;

        input.addEventListener('input', function () {
            let value = this.value.replace(/[^А-Яа-яЁё]/g, '').toLowerCase();

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
    const passwordFields = document.querySelectorAll('.password-sync');

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