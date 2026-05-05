@extends('layouts.app')

@section('title', 'добавить ребёнка')

@section('content')
<div class="container">
    <div class="account-form-wrap">
        <h1>добавить ребёнка</h1>

        @if ($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <ul style="margin: 0; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('account.children.store') }}" class="account-form">
            @csrf

            <div class="form-group">
                <label for="first_name">имя</label>
                <input
                    type="text"
                    id="first_name"
                    name="first_name"
                    class="form-control"
                    value="{{ old('first_name') }}"
                    required
                >
            </div>

            <div class="form-group">
                <label for="last_name">фамилия</label>
                <input
                    type="text"
                    id="last_name"
                    name="last_name"
                    class="form-control"
                    value="{{ old('last_name') }}"
                    required
                >
            </div>

            <div class="form-group">
                <label for="birth_date">дата рождения</label>
                <input
                    type="date"
                    id="birth_date"
                    name="birth_date"
                    class="form-control"
                    value="{{ old('birth_date') }}"
                >
            </div>

            <div class="form-group">
                <label for="gender">пол</label>
                <select id="gender" name="gender" class="form-control">
                    <option value="">не выбран</option>
                    <option value="male" {{ old('gender') === 'male' ? 'selected' : '' }}>мужской</option>
                    <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>женский</option>
                </select>
            </div>

            <div class="form-group">
                <label for="level">уровень подготовки</label>
                <input
                    type="text"
                    id="level"
                    name="level"
                    class="form-control"
                    value="{{ old('level') }}"
                    placeholder="например: начальный"
                >
            </div>

            <div class="form-group">
                <label for="notes">примечание</label>
                <textarea
                    id="notes"
                    name="notes"
                    class="form-control"
                    rows="4"
                    placeholder="дополнительная информация"
                >{{ old('notes') }}</textarea>
            </div>

            <div class="form-actions" style="display:flex; gap:12px; margin-top:20px;">
                <button type="submit" class="account-edit-btn">сохранить</button>
                <a href="{{ route('account') }}" class="account-edit-btn" style="text-decoration:none;">назад</a>
            </div>
        </form>
    </div>
</div>
@endsection