@extends('layouts.app')

@section('title', 'админ-панель')

@section('content')
<div class="container">
    <h1 class="section-title">админ-панель</h1>

    <div class="account-layout admin-layout">

        <div class="account-content">

            {{-- редактировать помещения --}}
            <section id="admin-rooms" class="account-panel is-active">
                <div class="admin-section-header">
                    <div>
                        <h2>редактировать помещения</h2>
                        <p class="admin-subtitle">управление залами, кабинетами и другими помещениями клуба</p>
                    </div>

                    @if(\Illuminate\Support\Facades\Route::has('admin.rooms.create'))
                        <a href="{{ route('admin.rooms.create') }}" class="account-edit-btn">добавить помещение</a>
                    @endif
                </div>

                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>название</th>
                                <th>тип</th>
                                <th>описание</th>
                                <th>действия</th>
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
                                    <td>
                                        <div class="admin-table-actions">
                                            @if(\Illuminate\Support\Facades\Route::has('admin.rooms.edit'))
                                                <a href="{{ route('admin.rooms.edit', $room->id) }}" class="btn-save">редактировать</a>
                                            @endif

                                            @if(\Illuminate\Support\Facades\Route::has('admin.rooms.destroy'))
                                                <form method="POST" action="{{ route('admin.rooms.destroy', $room->id) }}" data-confirm="удалить помещение?">
                                                    @csrf
                                                    @method('delete')
                                                    <button type="submit" class="btn-cancel">удалить</button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-muted">помещения пока не добавлены.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>


            {{-- редактировать тренировки --}}
            <section id="admin-trainings" class="account-panel">
                <div class="admin-section-header">
                    <div>
                        <h2>редактировать тренировки</h2>
                        <p class="admin-subtitle">создание, просмотр и управление расписанием тренировок</p>
                    </div>

                    <div class="admin-header-actions">
                        @if(\Illuminate\Support\Facades\Route::has('admin.trainings.create'))
                            <a href="{{ route('admin.trainings.create') }}" class="account-edit-btn">добавить тренировку</a>
                        @endif

                        @if(\Illuminate\Support\Facades\Route::has('admin.cancellations'))
                            <a href="{{ route('admin.cancellations') }}" class="account-secondary-btn">запросы отмены</a>
                        @endif
                    </div>
                </div>

                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>дата</th>
                                <th>время</th>
                                <th>тип</th>
                                <th>тренер</th>
                                <th>помещение</th>
                                <th>цена</th>
                                <th>статус</th>
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
                                    <td>{{ (int)$training->price }} ₽</td>
                                    <td>
                                        @if($training->is_cancelled)
                                            <span class="badge badge--cancelled">отменена</span>
                                        @else
                                            <span class="badge">активна</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-muted">тренировки пока не добавлены.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>


            {{-- редактировать пользователей --}}
            <section id="admin-users" class="account-panel">
                <div class="admin-section-header">
                    <div>
                        <h2>редактировать пользователей</h2>
                        <p class="admin-subtitle">всего пользователей: {{ $users->count() }}</p>
                    </div>
                </div>

                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>имя</th>
                                <th>Email</th>
                                <th>возраст</th>
                                <th>роль</th>
                                <th>специализация</th>
                                <th>дата регистрации</th>
                                <th>действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>{{ $user->id }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->age }}</td>
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
                                                        <option value="{{ $key }}" {{ $user->role == $key ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                @if($user->role === 'trainer')
                                                    <select name="specialization" class="specialization-select" onchange="this.form.submit()">
                                                        @foreach($specializations as $key => $label)
                                                            <option value="{{ $key }}" {{ $user->specialization == $key ? 'selected' : '' }}>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="admin-stats">
                    <div class="stat-card">
                        <h3>статистика по ролям</h3>
                        <ul class="stats-list">
                            <li>пользователей: {{ $users->where('role', 'user')->count() }}</li>
                            <li>администраторов: {{ $users->where('role', 'admin')->count() }}</li>
                            <li>тренеров: {{ $users->where('role', 'trainer')->count() }}</li>
                        </ul>
                    </div>

                    <div class="stat-card">
                        <h3>статистика по специализациям</h3>
                        <ul class="stats-list">
                            <li>тренеры по теннису: {{ $users->where('specialization', 'tennis_trainer')->count() }}</li>
                            <li>тренеры по фитнесу: {{ $users->where('specialization', 'fitness_trainer')->count() }}</li>
                            <li>тренеры по йоге: {{ $users->where('specialization', 'yoga_trainer')->count() }}</li>
                            <li>массажисты: {{ $users->where('specialization', 'masseur')->count() }}</li>
                        </ul>
                    </div>
                </div>
            </section>


            {{-- редактировать корты --}}
            <section id="admin-courts" class="account-panel">
                <div class="admin-section-header">
                    <div>
                        <h2>редактировать корты</h2>
                        <p class="admin-subtitle">отдельный раздел для управления теннисными кортами</p>
                    </div>

                    @if(\Illuminate\Support\Facades\Route::has('admin.rooms.create'))
                        <a href="{{ route('admin.rooms.create', ['type' => 'tennis_court']) }}" class="account-edit-btn">добавить корт</a>
                    @endif
                </div>

                <div class="corts admin-courts-grid">
                    @forelse($rooms->where('type', 'tennis_court') as $court)
                        <div class="cort">
                            @if(!empty($court->image))
                                <img src="{{ asset('storage/' . $court->image) }}" alt="{{ $court->name }}">
                            @endif

                            <h3>{{ $court->name }}</h3>
                            <p>{{ $court->description ?? 'описание не указано' }}</p>

                            <div class="admin-card-actions">
                                @if(\Illuminate\Support\Facades\Route::has('admin.rooms.edit'))
                                    <a href="{{ route('admin.rooms.edit', $court->id) }}" class="account-edit-btn">
                                        редактировать
                                    </a>
                                @endif

                                @if(\Illuminate\Support\Facades\Route::has('admin.rooms.destroy'))
                                    <form method="POST" action="{{ route('admin.rooms.destroy', $court->id) }}" data-confirm="удалить корт?">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="account-secondary-btn">удалить</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="muted">корты пока не добавлены.</div>
                    @endforelse
                </div>
            </section>

        </div>


        {{-- боковое меню --}}
        <aside class="account-sidebar">
            <div class="account-sidebar__title">админ-панель</div>

            <button type="button" class="account-sidebar__link js-admin-tab is-active" data-tab="admin-rooms">
                редактировать помещения
            </button>

            <button type="button" class="account-sidebar__link js-admin-tab" data-tab="admin-trainings">
                редактировать тренировки
            </button>

            <button type="button" class="account-sidebar__link js-admin-tab" data-tab="admin-users">
                редактировать пользователей
            </button>

            <button type="button" class="account-sidebar__link js-admin-tab" data-tab="admin-courts">
                редактировать корты
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