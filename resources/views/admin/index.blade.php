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
                    <div class="admin-header-actions">
                        <button type="button" class="account-edit-btn js-open-room-create">
                            добавить помещение
                        </button>
                    </div>
                </div>

                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>название</th>
                                <th>тип</th>
                                <th>описание</th>
                                <th>подходит для тренировок</th>
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
                                        @php
                                            $suitableTypes = is_array($room->suitable_training_types) ? $room->suitable_training_types : [];
                                        @endphp

                                        @if(!empty($suitableTypes))
                                            @foreach($suitableTypes as $typeKey)
                                                <span class="badge">{{ $trainingTypes[$typeKey] ?? $typeKey }}</span>
                                            @endforeach
                                        @else
                                            <span class="text-muted">по умолчанию по типу помещения</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn-save js-open-room-edit"
                                            data-room-id="{{ $room->id }}"
                                            data-room-name="{{ $room->name }}"
                                            data-room-type="{{ $room->type }}"
                                            data-room-description="{{ $room->description ?? '' }}"
                                            data-room-photo="{{ $room->photo ?? '' }}"
                                            data-room-types='@json($suitableTypes)'
                                            data-room-update-url="{{ route('admin.rooms.update', $room) }}"
                                        >
                                            изменить
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted">помещения пока не добавлены.</td>
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
                        <p class="admin-subtitle">создание, просмотр и управление расписанием и настройками типов тренировок</p>
                    </div>

                    <div class="admin-header-actions">
                        <a href="{{ route('admin.trainings.create') }}" class="account-edit-btn">
                            добавить тренировку
                        </a>

                        <a href="{{ route('admin.cancellations') }}" class="account-secondary-btn">
                            запросы отмены
                        </a>
                    </div>
                </div>

                <div class="admin-courts-grid">
                    @foreach($trainingTypes as $typeKey => $typeLabel)
                        @php
                            $st = $trainingTypeSettings[$typeKey] ?? null;
                            $selectedTrainerIds = $st['trainer_ids'] ?? [];
                        @endphp
                        <div class="admin-court-card">
                            <h3>{{ $typeLabel }}</h3>
                            <form method="POST" action="{{ route('admin.trainings.settings.update', $typeKey) }}" class="admin-room-form">
                                @csrf

                                <div class="admin-room-grid">
                                    <div class="field">
                                        <label class="lbl" for="price_{{ $typeKey }}">цена (₽)</label>
                                        <input class="inp" id="price_{{ $typeKey }}" type="number" name="price" min="500" value="{{ $st['price'] ?? 1000 }}" required>
                                    </div>
                                    <div class="field">
                                        <label class="lbl" for="fixed_{{ $typeKey }}">фикс. мест (опционально)</label>
                                        <input class="inp" id="fixed_{{ $typeKey }}" type="number" name="persons_fixed" min="1" max="200" value="{{ $st['persons_fixed'] ?? '' }}">
                                    </div>
                                    <div class="field">
                                        <label class="lbl" for="min_{{ $typeKey }}">минимум мест</label>
                                        <input class="inp" id="min_{{ $typeKey }}" type="number" name="persons_min" min="1" max="200" value="{{ $st['persons_min'] ?? 1 }}" required>
                                    </div>
                                    <div class="field">
                                        <label class="lbl" for="max_{{ $typeKey }}">максимум мест</label>
                                        <input class="inp" id="max_{{ $typeKey }}" type="number" name="persons_max" min="1" max="200" value="{{ $st['persons_max'] ?? 20 }}" required>
                                    </div>
                                </div>

                                <div class="field">
                                    <div class="lbl">тренеры, которые ведут этот тип</div>
                                    <div class="admin-checkboxes">
                                        @forelse($trainersByType[$typeKey] ?? [] as $trainer)
                                            @php
                                                $trainerName = trim(($trainer->first_name ?? '') . ' ' . ($trainer->last_name ?? ''));
                                            @endphp
                                            <label class="admin-check">
                                                <input type="checkbox" name="trainer_ids[]" value="{{ $trainer->id }}" {{ in_array((int)$trainer->id, $selectedTrainerIds) ? 'checked' : '' }}>
                                                <span>{{ $trainerName ?: ('ID ' . $trainer->id) }}</span>
                                            </label>
                                        @empty
                                            <span class="text-muted">нет тренеров с подходящей специализацией</span>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="field">
                                    <div class="lbl">дни недели для постановки в календарь</div>
                                    <div class="admin-checkboxes">
                                        @foreach($weekDayNames as $dayNum => $dayLabel)
                                            <label class="admin-check">
                                                <input type="checkbox" name="weekdays[]" value="{{ $dayNum }}" {{ in_array($dayNum, $st['weekdays'] ?? [1,2,3,4,5,6,7]) ? 'checked' : '' }}>
                                                <span>{{ $dayLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="admin-room-grid">
                                    <div class="field">
                                        <label class="lbl" for="tstart_{{ $typeKey }}">время начала</label>
                                        <input class="inp" id="tstart_{{ $typeKey }}" type="time" name="time_start" value="{{ $st['time_start'] ?? '08:00' }}" required>
                                    </div>
                                    <div class="field">
                                        <label class="lbl" for="tend_{{ $typeKey }}">время окончания</label>
                                        <input class="inp" id="tend_{{ $typeKey }}" type="time" name="time_end" value="{{ $st['time_end'] ?? '22:00' }}" required>
                                    </div>
                                </div>

                                <div class="admin-actions">
                                    <button type="submit" class="btn-save">сохранить настройки</button>
                                </div>
                            </form>
                        </div>
                    @endforeach
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
                                    <td>{{ (int) $training->price }} ₽</td>
                                    <td>
                                        @if(!empty($training->is_cancelled))
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
                                    <td colspan="8" class="text-muted">пользователи пока не найдены.</td>
                                </tr>
                            @endforelse
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


            {{-- аренда кортов --}}
            <section id="admin-courts" class="account-panel">
                <div class="admin-section-header">
                    <div>
                        <h2>аренда кортов</h2>
                        <p class="admin-subtitle">настройка дней недели и времени доступности для аренды</p>
                    </div>
                </div>

                <div class="admin-courts-grid">
                    @forelse($rooms->where('type', 'tennis_court') as $court)
                        @php
                            $rentDays = is_array($court->rent_weekdays) && !empty($court->rent_weekdays) ? $court->rent_weekdays : [1,2,3,4,5,6,7];
                            $rentStart = $court->rent_start_time ? \Carbon\Carbon::parse($court->rent_start_time)->format('H:i') : '08:00';
                            $rentEnd = $court->rent_end_time ? \Carbon\Carbon::parse($court->rent_end_time)->format('H:i') : '22:00';
                        @endphp

                        <div class="admin-court-card">
                            <h3>{{ $court->name }}</h3>

                            <form method="POST" action="{{ route('admin.courts.rent-settings', $court) }}" class="admin-room-form">
                                @csrf

                                <div class="field">
                                    <div class="lbl">дни недели для аренды</div>
                                    <div class="admin-checkboxes">
                                        @foreach($weekDayNames as $dayNum => $dayLabel)
                                            <label class="admin-check">
                                                <input type="checkbox" name="rent_weekdays[]" value="{{ $dayNum }}" {{ in_array($dayNum, $rentDays) ? 'checked' : '' }}>
                                                <span>{{ $dayLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="admin-room-grid">
                                    <div class="field">
                                        <label class="lbl" for="rent_start_{{ $court->id }}">начало аренды</label>
                                        <input id="rent_start_{{ $court->id }}" class="inp" type="time" name="rent_start_time" value="{{ $rentStart }}" required>
                                    </div>

                                    <div class="field">
                                        <label class="lbl" for="rent_end_{{ $court->id }}">конец аренды</label>
                                        <input id="rent_end_{{ $court->id }}" class="inp" type="time" name="rent_end_time" value="{{ $rentEnd }}" required>
                                    </div>
                                </div>

                                <div class="admin-actions">
                                    <button type="submit" class="btn-save">сохранить доступность</button>
                                </div>
                            </form>
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
                аренда кортов
            </button>
        </aside>
    </div>
</div>

<div id="roomCreateModal" class="modal is-hidden" aria-hidden="true">
    <div class="modal__overlay js-modal-close"></div>
    <div class="modal__dialog">
        <button type="button" class="modal__close js-modal-close">&times;</button>
        <div class="modal__header">
            <div class="modal__title">добавить помещение</div>
            <div class="modal__subtitle">заполните параметры нового помещения</div>
        </div>
        <div class="modal__body">
            <form method="POST" action="{{ route('admin.rooms.store') }}" enctype="multipart/form-data" class="admin-room-form">
                @csrf
                <div class="admin-room-grid">
                    <div class="field">
                        <label class="lbl" for="room-create-name">название</label>
                        <input id="room-create-name" class="inp" type="text" name="name" required>
                    </div>

                    <div class="field">
                        <label class="lbl" for="room-create-type">тип помещения</label>
                        <select id="room-create-type" class="inp" name="type" required>
                            @foreach($roomTypeNames as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label class="lbl" for="room-create-photo">фото</label>
                        <input id="room-create-photo" class="inp" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
                    </div>

                    <div class="field">
                        <label class="lbl" for="room-create-description">описание</label>
                        <textarea id="room-create-description" class="inp" name="description" rows="4"></textarea>
                    </div>
                </div>

                <div class="field">
                    <div class="lbl">подходящие типы тренировок</div>
                    <div class="admin-checkboxes">
                        @foreach($trainingTypes as $typeValue => $typeLabel)
                            <label class="admin-check">
                                <input type="checkbox" name="suitable_training_types[]" value="{{ $typeValue }}">
                                <span>{{ $typeLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="admin-actions">
                    <button type="submit" class="btn-save">сохранить</button>
                    <button type="button" class="btn-cancel js-modal-close">отмена</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="roomEditModal" class="modal is-hidden" aria-hidden="true">
    <div class="modal__overlay js-modal-close"></div>
    <div class="modal__dialog">
        <button type="button" class="modal__close js-modal-close">&times;</button>
        <div class="modal__header">
            <div class="modal__title">изменить помещение</div>
            <div class="modal__subtitle">отредактируйте название, фото и доступные типы тренировок</div>
        </div>
        <div class="modal__body">
            <form id="roomEditForm" method="POST" action="" enctype="multipart/form-data" class="admin-room-form">
                @csrf
                <div class="admin-room-grid">
                    <div class="field">
                        <label class="lbl" for="room-edit-name">название</label>
                        <input id="room-edit-name" class="inp" type="text" name="name" required>
                    </div>

                    <div class="field">
                        <label class="lbl" for="room-edit-type">тип помещения</label>
                        <select id="room-edit-type" class="inp" name="type" required>
                            @foreach($roomTypeNames as $typeValue => $typeLabel)
                                <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label class="lbl" for="room-edit-photo">новое фото</label>
                        <input id="room-edit-photo" class="inp" type="file" name="photo" accept=".jpg,.jpeg,.png,.webp">
                        <div id="roomEditPhotoHint" class="hint"></div>
                    </div>

                    <div class="field">
                        <label class="lbl" for="room-edit-description">описание</label>
                        <textarea id="room-edit-description" class="inp" name="description" rows="4"></textarea>
                    </div>
                </div>

                <div class="field">
                    <label class="admin-check">
                        <input type="checkbox" name="remove_photo" value="1">
                        <span>удалить текущее фото</span>
                    </label>
                </div>

                <div class="field">
                    <div class="lbl">подходящие типы тренировок</div>
                    <div class="admin-checkboxes">
                        @foreach($trainingTypes as $typeValue => $typeLabel)
                            <label class="admin-check">
                                <input class="js-edit-training-type" type="checkbox" name="suitable_training_types[]" value="{{ $typeValue }}">
                                <span>{{ $typeLabel }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="admin-actions">
                    <button type="submit" class="btn-save">сохранить изменения</button>
                    <button type="button" class="btn-cancel js-modal-close">отмена</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.js-admin-tab');
    const panels = document.querySelectorAll('.account-panel');
    const createModal = document.getElementById('roomCreateModal');
    const editModal = document.getElementById('roomEditModal');
    const editForm = document.getElementById('roomEditForm');

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

    function openModal(modal) {
        if (!modal) {
            return;
        }
        modal.classList.remove('is-hidden');
        document.body.classList.add('no-scroll');
    }

    function closeModals() {
        document.querySelectorAll('.modal').forEach(function (modal) {
            modal.classList.add('is-hidden');
        });
        document.body.classList.remove('no-scroll');
    }

    document.querySelectorAll('.js-open-room-create').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(createModal);
        });
    });

    document.querySelectorAll('.js-open-room-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!editForm) {
                return;
            }

            editForm.action = btn.dataset.roomUpdateUrl || '';
            document.getElementById('room-edit-name').value = btn.dataset.roomName || '';
            document.getElementById('room-edit-type').value = btn.dataset.roomType || '';
            document.getElementById('room-edit-description').value = btn.dataset.roomDescription || '';

            const photoHint = document.getElementById('roomEditPhotoHint');
            if (photoHint) {
                photoHint.textContent = btn.dataset.roomPhoto ? ('текущее фото: ' + btn.dataset.roomPhoto) : 'фото не загружено';
            }

            const selectedTypes = JSON.parse(btn.dataset.roomTypes || '[]');
            document.querySelectorAll('.js-edit-training-type').forEach(function (checkbox) {
                checkbox.checked = selectedTypes.includes(checkbox.value);
            });

            const removePhotoCheckbox = editForm.querySelector('input[name="remove_photo"]');
            if (removePhotoCheckbox) {
                removePhotoCheckbox.checked = false;
            }

            const photoInput = document.getElementById('room-edit-photo');
            if (photoInput) {
                photoInput.value = '';
            }

            openModal(editModal);
        });
    });

    document.querySelectorAll('.js-modal-close').forEach(function (btn) {
        btn.addEventListener('click', closeModals);
    });
});
</script>
@endsection