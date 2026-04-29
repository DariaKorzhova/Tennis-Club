@extends('layouts.app')

@section('title', 'Админ-панель')

@section('content')
<div class="container">
    <h1 class="section-title">Админ-панель</h1>

    <div class="account-layout admin-layout">
        <div class="account-content">

            {{-- РЕДАКТИРОВАТЬ ПОМЕЩЕНИЯ --}}
            <section id="admin-rooms" class="account-panel is-active">
                <div class="admin-section-header">
                    <div>
                        <h2>Редактировать помещения</h2>
                        <p class="admin-subtitle">Управление залами, кабинетами и другими помещениями клуба</p>
                    </div>
                </div>

                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Название</th>
                                <th>Тип</th>
                                <th>Описание</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rooms as $room)
                                <tr>
                                    <td>{{ $room->id }}</td>
                                    <td>{{ $room->name }}</td>
                                    <td>
                                        <span class="specialization-badge">
                                            {{ $roomTypeNames[$room->type] ?? $room->type }}
                                        </span>
                                    </td>
                                    <td>{{ $room->description ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted">Помещения пока не добавлены.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>


            {{-- РЕДАКТИРОВАТЬ ТРЕНИРОВКИ --}}
            <section id="admin-trainings" class="account-panel">
                <div class="admin-section-header">
                    <div>
                        <h2>Редактировать тренировки</h2>
                        <p class="admin-subtitle">Создание, просмотр и управление расписанием тренировок</p>
                    </div>

                    <div class="admin-header-actions">
                        <a href="{{ route('admin.trainings.create') }}" class="account-edit-btn">
                            Добавить тренировку
                        </a>

                        <a href="{{ route('admin.cancellations') }}" class="account-secondary-btn">
                            Запросы отмены
                        </a>
                    </div>
                </div>

                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Дата</th>
                                <th>Время</th>
                                <th>Тип</th>
                                <th>Тренер</th>
                                <th>Помещение</th>
                                <th>Цена</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trainings as $training)
                                @php
                                    $room = $training->rooms->first();
                                @endphp

                                <tr>
                                    <td>{{ $training->id }}</td>
                                    <td>{{ \Carbon\Carbon::parse($training->date)->format('d.m.Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($training->time)->format('H:i') }}</td>
                                    <td>{{ $typeNames[$training->type] ?? $training->type }}</td>
                                    <td>{{ $training->trainer->name ?? '—' }}</td>
                                    <td>{{ $room->name ?? '—' }}</td>
                                    <td>{{ (int) $training->price }} ₽</td>
                                    <td>
                                        @if(!empty($training->is_cancelled))
                                            <span class="badge badge--cancelled">Отменена</span>
                                        @else
                                            <span class="badge">Активна</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-muted">Тренировки пока не добавлены.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>


            {{-- РЕДАКТИРОВАТЬ ПОЛЬЗОВАТЕЛЕЙ --}}
            <section id="admin-users" class="account-panel">
                <div class="admin-section-header">
                    <div>
                        <h2>Редактировать пользователей</h2>
                        <p class="admin-subtitle">Всего пользователей: {{ $users->count() }}</p>
                    </div>
                </div>

                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Имя</th>
                                <th>Email</th>
                                <th>Возраст</th>
                                <th>Роль</th>
                                <th>Специализация</th>
                                <th>Дата регистрации</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->age ?? '—' }}</td>
                                    <td>
                                        <span class="role-badge role-{{ $user->role }}">
                                            {{ $roles[$user->role] ?? $user->role }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($user->role === 'trainer' && $user->specialization)
                                            <span class="specialization-badge">
                                                {{ $specializations[$user->specialization] ?? $user->specialization }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $user->created_at->format('d.m.Y H:i') }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.users.update-role', $user) }}" class="role-form">
                                            @csrf

                                            <div class="role-selector">
                                                <select name="role" class="role-select" onchange="this.form.submit()">
                                                    @foreach($roles as $key => $label)
                                                        <option value="{{ $key }}" {{ $user->role === $key ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                @if($user->role === 'trainer')
                                                    <select name="specialization" class="specialization-select" onchange="this.form.submit()">
                                                        @foreach($specializations as $key => $label)
                                                            <option value="{{ $key }}" {{ $user->specialization === $key ? 'selected' : '' }}>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-muted">Пользователи пока не найдены.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="admin-stats">
                    <div class="stat-card">
                        <h3>Статистика по ролям</h3>
                        <ul class="stats-list">
                            <li>Пользователей: {{ $users->where('role', 'user')->count() }}</li>
                            <li>Администраторов: {{ $users->where('role', 'admin')->count() }}</li>
                            <li>Тренеров: {{ $users->where('role', 'trainer')->count() }}</li>
                        </ul>
                    </div>

                    <div class="stat-card">
                        <h3>Статистика по специализациям</h3>
                        <ul class="stats-list">
                            <li>Тренеры по теннису: {{ $users->where('specialization', 'tennis_trainer')->count() }}</li>
                            <li>Тренеры по фитнесу: {{ $users->where('specialization', 'fitness_trainer')->count() }}</li>
                            <li>Тренеры по йоге: {{ $users->where('specialization', 'yoga_trainer')->count() }}</li>
                            <li>Массажисты: {{ $users->where('specialization', 'masseur')->count() }}</li>
                        </ul>
                    </div>
                </div>
            </section>


            {{-- РЕДАКТИРОВАТЬ КОРТЫ --}}
            <section id="admin-courts" class="account-panel">
                <div class="admin-section-header">
                    <div>
                        <h2>Редактировать корты</h2>
                        <p class="admin-subtitle">Отдельный раздел для управления теннисными кортами</p>
                    </div>
                </div>

                <div class="corts admin-courts-grid">
                    @forelse($rooms->where('type', 'tennis_court') as $court)
                        <div class="cort">
                            @if(!empty($court->image))
                                <img src="{{ asset('storage/' . $court->image) }}" alt="{{ $court->name }}">
                            @endif

                            <div class="roomName">
                                <h3>{{ $court->name }}</h3>
                            </div>

                            <p>{{ $court->description ?? 'Описание не указано' }}</p>

                            <div>
                                <span>Теннисный корт</span>
                            </div>
                        </div>
                    @empty
                        <div class="muted">Корты пока не добавлены.</div>
                    @endforelse
                </div>
            </section>

        </div>


        {{-- БОКОВОЕ МЕНЮ --}}
        <aside class="account-sidebar">
            <div class="account-sidebar__title">Админ-панель</div>

            <button type="button" class="account-sidebar__link js-admin-tab is-active" data-tab="admin-rooms">
                Редактировать помещения
            </button>

            <button type="button" class="account-sidebar__link js-admin-tab" data-tab="admin-trainings">
                Редактировать тренировки
            </button>

            <button type="button" class="account-sidebar__link js-admin-tab" data-tab="admin-users">
                Редактировать пользователей
            </button>

            <button type="button" class="account-sidebar__link js-admin-tab" data-tab="admin-courts">
                Редактировать корты
            </button>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.js-admin-tab');
    const panels = document.querySelectorAll('.account-panel');

    function openTab(tabId) {
        tabButtons.forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-tab') === tabId);
        });

        panels.forEach(function (panel) {
            panel.classList.toggle('is-active', panel.id === tabId);
        });

        try {
            localStorage.setItem('adminActiveTab', tabId);
        } catch (e) {}
    }

    tabButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            openTab(btn.getAttribute('data-tab'));
        });
    });

    let initialTab = 'admin-rooms';

    try {
        const saved = localStorage.getItem('adminActiveTab');

        if (saved && document.getElementById(saved)) {
            initialTab = saved;
        }
    } catch (e) {}

    openTab(initialTab);
});
</script>
@endsection