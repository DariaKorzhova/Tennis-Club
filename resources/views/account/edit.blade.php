@extends('layouts.app')

@section('title', 'редактирование аккаунта')

@section('content')
<div class="container">
    <div class="account-layout-edit">

        <div class="account-content">
            <div class="account-edit-shell">
                <a href="{{ route('account') }}" class="account-back-btn">← назад</a>
                <div class="account-edit-card">
                    
                    <h2 class="account-edit-title">редактирование аккаунта</h2>

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
                                <label class="form-label" for="first_name">имя</label>
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
                                <label class="form-label" for="last_name">фамилия</label>
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
                            <label class="form-label" for="birth_date">дата рождения</label>
                            <input
                                type="date"
                                class="form-input"
                                id="birth_date"
                                name="birth_date"
                                value="{{ old('birth_date', $user->birth_date ? $user->birth_date->format('Y-m-d') : '') }}"
                                max="{{ now()->subDay()->format('Y-m-d') }}"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="photo">фото профиля</label>
                            <input
                                type="file"
                                class="form-input"
                                id="photo"
                                name="photo"
                                accept=".jpg,.jpeg,.png,.webp"
                            >
                        </div>

                        <input type="hidden" name="crop_x" id="crop_x" value="0">
                        <input type="hidden" name="crop_y" id="crop_y" value="0">
                        <input type="hidden" name="crop_size" id="crop_size" value="0">

                        <div class="form-group" id="cropSection" style="display:none;">
                            <label class="form-label">выберите видимую квадратную область</label>

                            <div style="position:relative; width:420px; max-width:100%; border:1px solid #ddd; overflow:hidden;" id="cropWrap">
                                <img id="cropPreview" src="" alt="предпросмотр" style="display:block; max-width:100%; width:100%;">
                                <div id="cropBox"
                                     style="position:absolute; border:2px solid #DFFF4F; box-shadow:0 0 0 9999px rgba(0,0,0,.35); cursor:move; width:180px; height:180px; left:40px; top:40px;">
                                </div>
                            </div>

                            <div style="margin-top:10px; color:#666;">
                                перетащи квадрат мышкой. будет сохранена видимая часть фото.
                            </div>
                        </div>

                        <div class="account-edit-buttons">
                            <button type="submit" class="form-button">сохранить изменения</button>
                            <a href="{{ route('account') }}" class="account-secondary-btn">назад</a>
                        </div>
                    </form>
                </div>

                <div class="account-edit-card">
                    <h3 class="account-edit-subtitle">смена пароля</h3>
                    <p class="account-edit-hint">сначала введите новый пароль, затем получите код подтверждения на почту и подтвердите смену.</p>

                    <form method="POST" action="{{ route('account.password.send-code') }}" class="account-password-form">
                        @csrf

                        <div class="form-group">
                            <label class="form-label" for="new_password">новый пароль</label>
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
                            <label class="form-label" for="new_password_confirmation">подтвердите новый пароль</label>
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
                            <label class="checkbox-label" for="show_passwords">показать пароль</label>
                        </div>

                        <button type="submit" class="form-button">отправить код на почту</button>
                    </form>

                    <form method="POST" action="{{ route('account.password.update') }}" class="account-password-confirm-form">
                        @csrf

                        <div class="form-group">
                            <label class="form-label" for="code">код подтверждения</label>
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

                        <button type="submit" class="form-button">подтвердить смену пароля</button>
                    </form>
                </div>
            </div>
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

    const toggle = document.getElementById('show_passwords');
    const passwordFields = document.querySelectorAll('.password-sync');

    if (toggle) {
        toggle.addEventListener('change', function () {
            passwordFields.forEach(function (field) {
                field.type = toggle.checked ? 'text' : 'password';
            });
        });
    }

    const photoInput = document.getElementById('photo');
    const cropSection = document.getElementById('cropSection');
    const cropPreview = document.getElementById('cropPreview');
    const cropWrap = document.getElementById('cropWrap');
    const cropBox = document.getElementById('cropBox');

    const cropX = document.getElementById('crop_x');
    const cropY = document.getElementById('crop_y');
    const cropSize = document.getElementById('crop_size');

    if (!photoInput || !cropSection || !cropPreview || !cropWrap || !cropBox || !cropX || !cropY || !cropSize) {
        return;
    }

    let naturalWidth = 0;
    let naturalHeight = 0;
    let drag = false;
    let startX = 0;
    let startY = 0;
    let startLeft = 0;
    let startTop = 0;

    function syncCropInputs() {
        const imgRect = cropPreview.getBoundingClientRect();
        const boxRect = cropBox.getBoundingClientRect();

        if (!imgRect.width || !imgRect.height) {
            return;
        }

        const scaleX = naturalWidth / imgRect.width;
        const scaleY = naturalHeight / imgRect.height;

        cropX.value = Math.round((boxRect.left - imgRect.left) * scaleX);
        cropY.value = Math.round((boxRect.top - imgRect.top) * scaleY);
        cropSize.value = Math.round(boxRect.width * scaleX);
    }

    function clampBox() {
        const wrapRect = cropWrap.getBoundingClientRect();
        const boxRect = cropBox.getBoundingClientRect();

        let left = parseFloat(cropBox.style.left || '0');
        let top = parseFloat(cropBox.style.top || '0');

        const maxLeft = Math.max(0, wrapRect.width - boxRect.width);
        const maxTop = Math.max(0, wrapRect.height - boxRect.height);

        if (left < 0) left = 0;
        if (top < 0) top = 0;
        if (left > maxLeft) left = maxLeft;
        if (top > maxTop) top = maxTop;

        cropBox.style.left = left + 'px';
        cropBox.style.top = top + 'px';

        syncCropInputs();
    }

    photoInput.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (event) {
            cropPreview.src = event.target.result;
            cropPreview.onload = function () {
                naturalWidth = cropPreview.naturalWidth;
                naturalHeight = cropPreview.naturalHeight;

                cropSection.style.display = 'block';

                const width = cropPreview.clientWidth;
                const startSize = Math.min(width * 0.55, 180);

                cropBox.style.width = startSize + 'px';
                cropBox.style.height = startSize + 'px';
                cropBox.style.left = '20px';
                cropBox.style.top = '20px';

                syncCropInputs();
            };
        };
        reader.readAsDataURL(file);
    });

    cropBox.addEventListener('mousedown', function (e) {
        drag = true;
        startX = e.clientX;
        startY = e.clientY;
        startLeft = parseFloat(cropBox.style.left || '0');
        startTop = parseFloat(cropBox.style.top || '0');
        e.preventDefault();
    });

    document.addEventListener('mousemove', function (e) {
        if (!drag) return;

        const dx = e.clientX - startX;
        const dy = e.clientY - startY;

        cropBox.style.left = startLeft + dx + 'px';
        cropBox.style.top = startTop + dy + 'px';

        clampBox();
    });

    document.addEventListener('mouseup', function () {
        drag = false;
    });

    window.addEventListener('resize', function () {
        if (cropSection.style.display !== 'none') {
            clampBox();
        }
    });
});
</script>
@endsection