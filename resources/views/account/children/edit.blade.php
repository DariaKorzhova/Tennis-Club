@extends('layouts.app')

@section('title', 'Редактировать ребёнка')

@section('content')
<div class="container">
    <div class="account-form-wrap">
        <h1>Редактировать данные ребёнка</h1>

        @if ($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <ul style="margin: 0; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('account.children.update', $child->id) }}" class="account-form">
            @csrf

            <div class="form-group">
                <label for="first_name">Имя</label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    class="form-control"
                    value="{{ old('first_name', $child->first_name) }}"
                    required
                >
            </div>

            <div class="form-group">
                <label for="last_name">Фамилия</label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    class="form-control"
                    value="{{ old('last_name', $child->last_name) }}"
                    required
                >
            </div>

            <div class="form-group">
                <label for="birth_date">Дата рождения</label>
                <input
                    type="date"
                    id="birth_date"
                    name="birth_date"
                    class="form-control"
                    value="{{ old('birth_date', optional($child->birth_date)->format('Y-m-d')) }}"
                >
            </div>

            <div class="form-group">
                <label for="gender">Пол</label>
                <select id="gender" name="gender" class="form-control">
                    <option value="">Не выбран</option>
                    <option value="male" {{ old('gender', $child->gender) === 'male' ? 'selected' : '' }}>Мужской</option>
                    <option value="female" {{ old('gender', $child->gender) === 'female' ? 'selected' : '' }}>Женский</option>
                </select>
            </div>

            <div class="form-group">
                <label for="level">Уровень подготовки</label>
                <input
                    type="text"
                    id="level"
                    name="level"
                    class="form-control"
                    value="{{ old('level', $child->level) }}"
                    placeholder="Например: начальный"
                >
            </div>

            <div class="form-group">
                <label for="notes">Примечание</label>
                <textarea
                    id="notes"
                    name="notes"
                    class="form-control"
                    rows="4"
                    placeholder="Дополнительная информация"
                >{{ old('notes', $child->notes) }}</textarea>
            </div>

            <div class="form-actions" style="display:flex; gap:12px; margin-top:20px;">
                <button type="submit" class="account-edit-btn">Сохранить изменения</button>
                <a href="{{ route('account') }}" class="account-edit-btn" style="text-decoration:none;">Назад</a>
            </div>
        </form>
    </div>
</div>
@endsection