@extends('layouts.app')

@section('title', 'Тренировки')

@section('content')
<div class="container">
    <h1>Календарь тренировок</h1>

    @php
        $user = auth()->user();
        $isAdmin = $user && $user->isAdmin();

        $currentOffset = (int)($dayOffset ?? 0);
        if ($currentOffset < 0) $currentOffset = 0;
        if ($currentOffset > 3) $currentOffset = 3;

        $prevOffset = $currentOffset - 1; if ($prevOffset < 0) $prevOffset = 0;
        $nextOffset = $currentOffset + 1; if ($nextOffset > 3) $nextOffset = 3;

        $prevDisabled = ($currentOffset === 0);
        $nextDisabled = ($currentOffset === 3);
    @endphp

    <div class="filter-section">
        <form method="GET" action="{{ route('trainings.show') }}" class="row" id="filterForm">
            <input type="hidden" name="week" value="{{ $currentOffset }}">

            <label class="form-label" for="type">Тип:</label>
            <select name="type" id="type" class="form-select" onchange="this.form.submit()">
                <option value="all" {{ $selectedType == 'all' ? 'selected' : '' }}>Все</option>
                @foreach($types as $key => $name)
                    <option value="{{ $key }}" {{ $selectedType == $key ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>

            <label class="form-label" for="room">Помещение:</label>
            <select name="room" id="room" class="form-select" onchange="this.form.submit()">
                @foreach($rooms as $key => $name)
                    <option value="{{ $key }}" {{ $selectedRoom == $key ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>

            <label class="form-label" for="trainer">Тренер:</label>
            <select name="trainer" id="trainer" class="form-select" onchange="this.form.submit()">
                @foreach($trainers as $key => $name)
                    <option value="{{ $key }}" {{ (string)$selectedTrainer === (string)$key ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>

            <a href="{{ route('trainings.show', ['week' => $currentOffset]) }}" class="btn-secondary">Сбросить</a>
        </form>

        @if($isAdmin)
            <div class="admin-add-under-filters">
                <a class="btn-admin-add" href="{{ route('admin.trainings.create') }}">Добавить тренировку</a>
                <a class="btn-admin-cancel" href="{{ route('admin.cancellations') }}">Запросы отмены</a>
            </div>
        @endif
    </div>

    <div class="calendar-wrap">
        @if(!$prevDisabled)
            <a class="calendar-arrow calendar-arrow--left"
               href="{{ route('trainings.show', ['week'=>$prevOffset,'type'=>$selectedType,'room'=>$selectedRoom,'trainer'=>$selectedTrainer]) }}"
               aria-label="Предыдущие 7 дней">‹</a>
        @else
            <span class="calendar-arrow calendar-arrow--left is-disabled" aria-disabled="true">‹</span>
        @endif

        <div class="calendar-inner">
            <table class="calendar">
                <thead>
                <tr>
                    <th class="time-column">Время</th>
                    @foreach($calendarData['days'] as $day)
                        @php $isToday = (bool)($day['isToday'] ?? false); @endphp
                        <th class="{{ $isToday ? 'is-today' : '' }}">
                            <div class="day-header">{{ $day['name'] }}</div>
                            <div>{{ $day['dateFormatted'] }}</div>
                        </th>
                    @endforeach
                </tr>
                </thead>

                <tbody>
                @foreach($calendarData['times'] as $time)
                    <tr>
                        <td class="time-column">{{ $time }}</td>

                        @foreach($calendarData['days'] as $day)
                            @php
                                $cellDate = $day['date'] ?? null;
                                $cellTime = $time;
                                $hasTrainings = isset($day['trainings'][$time]) && !empty($day['trainings'][$time]);
                            @endphp

                            <td class="{{ ($day['isToday'] ?? false) ? 'is-today' : '' }}">
                                <div class="cal-cell {{ $hasTrainings ? 'cal-cell--has' : 'cal-cell--empty' }}">
                                    <div class="cal-stack">
                                        @if($hasTrainings)
                                            @foreach($day['trainings'][$time] as $t)
                                                @php
                                                    $bg = !empty($t['is_full']) ? '#BDBDBD' : ($t['color'] ?? '#777777');
                                                    if (!empty($t['is_booked_by_me'])) {
                                                        $bg = '#111111';
                                                    }
                                                @endphp

                                                <button
                                                    type="button"
                                                    class="training training--compact js-open-training {{ !empty($t['is_full']) ? 'training--full' : '' }} {{ !empty($t['is_booked_by_me']) ? 'training--mine' : '' }}"
                                                    style="background-color: {{ $bg }};"
                                                    data-training='@json($t)'
                                                >
                                                    <div class="training-compact__top">
                                                        <div class="training-type">
                                                            <p>{{ $t['type_name'] }}</p> 
                                                            @if(!empty($t['is_booked_by_me']))
                                                                <span class="training-booked-mark">вы записаны</span>
                                                            @endif
                                                        </div>
                                                        <div class="training-duration">{{ $t['duration'] }}</div>
                                                    </div>
                                                    <div class="training-compact__price">{{ (int)$t['price'] }} ₽ / чел</div>
                                                </button>
                                            @endforeach
                                        @else
                                            <div class="empty-cell">-</div>
                                        @endif
                                    </div>

                                    @if($isAdmin && $cellDate)
                                        <button
                                            type="button"
                                            class="cell-add-wide js-open-admin-add"
                                            data-date="{{ $cellDate }}"
                                            data-time="{{ $cellTime }}"
                                            aria-label="Добавить тренировку"
                                            title="Добавить тренировку"
                                        >
                                            <span class="cell-add-wide__plus">+</span>
                                            <span class="cell-add-wide__text">Добавить</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if(!$nextDisabled)
            <a class="calendar-arrow calendar-arrow--right"
               href="{{ route('trainings.show', ['week'=>$nextOffset,'type'=>$selectedType,'room'=>$selectedRoom,'trainer'=>$selectedTrainer]) }}"
               aria-label="Следующие 7 дней">›</a>
        @else
            <span class="calendar-arrow calendar-arrow--right is-disabled" aria-disabled="true">›</span>
        @endif
    </div>
</div>

<div class="modal is-hidden" id="trainingModal" aria-hidden="true">
    <div class="modal__overlay" data-close="1"></div>

    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="trainingModalTitle">
        <button class="modal__close" type="button" data-close="1">×</button>

        <div class="modal__header">
            <div class="modal__title" id="trainingModalTitle">Тренировка</div>
            <div class="modal__subtitle muted" id="trainingModalSubtitle"></div>
        </div>

        <div class="modal__body">
            <div class="modal-grid">
                <div class="modal-line"><span class="k">Тип:</span> <span class="v" id="mType"></span></div>
                <div class="modal-line"><span class="k">Дата:</span> <span class="v" id="mDate"></span></div>
                <div class="modal-line"><span class="k">Время:</span> <span class="v" id="mTime"></span></div>
                <div class="modal-line"><span class="k">Длительность:</span> <span class="v" id="mDuration"></span></div>
                <div class="modal-line"><span class="k">Цена:</span> <span class="v" id="mPrice"></span></div>
                <div class="modal-line"><span class="k">Мест:</span> <span class="v" id="mSeats"></span></div>
                <div class="modal-line"><span class="k">Свободно:</span> <span class="v" id="mFree"></span></div>
                <div class="modal-line"><span class="k">Тренер:</span> <span class="v" id="mTrainer"></span></div>
                <div class="modal-line"><span class="k">Место:</span> <span class="v" id="mRoom"></span></div>
                <div class="modal-actions">
                    <form method="POST" action="#" id="formBook" class="is-hidden">
                        @csrf
                        <button type="submit" class="btn-card btn-card--success">Записаться</button>
                    </form>

                    <form method="POST" action="#" id="formCancel" class="is-hidden" data-confirm="Отменить запись на тренировку?">
                        @csrf
                        <button type="submit" class="btn-card btn-card--danger">Отменить запись</button>
                    </form>

                    <form method="POST" action="#" id="formTrainerRequest" class="is-hidden">
                        @csrf
                        <input class="input-mini" type="text" name="reason" placeholder="Причина (опционально)">
                        <button type="submit" class="btn-card btn-card--warning">Запросить отмену</button>
                    </form>

                    <div class="muted is-hidden" id="modalInfo"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('trainingModal');
    var overlay = modal ? modal.querySelector('.modal__overlay') : null;

    var elType = document.getElementById('mType');
    var elDate = document.getElementById('mDate');
    var elTime = document.getElementById('mTime');
    var elDuration = document.getElementById('mDuration');
    var elPrice = document.getElementById('mPrice');
    var elSeats = document.getElementById('mSeats');
    var elFree = document.getElementById('mFree');
    var elTrainer = document.getElementById('mTrainer');
    var elRoom = document.getElementById('mRoom');

    var formBook = document.getElementById('formBook');
    var formCancel = document.getElementById('formCancel');
    var formReq = document.getElementById('formTrainerRequest');
    var info = document.getElementById('modalInfo');

    function openModal() {
        modal.classList.remove('is-hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('no-scroll');

        const dialog = modal.querySelector('.modal__dialog');
        if (dialog) {
            dialog.scrollTop = 0;
        }

        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function closeModal() {
        modal.classList.add('is-hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('no-scroll');
    }

    if (overlay) overlay.addEventListener('click', closeModal);
    modal.querySelectorAll('[data-close="1"]').forEach(function (x) {
        x.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && !modal.classList.contains('is-hidden')) closeModal();
    });

    document.querySelectorAll('.js-open-training').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var t = {};
            try { t = JSON.parse(btn.getAttribute('data-training') || '{}'); } catch (e) {}

            elType.textContent = t.type_name || '—';
            elDate.textContent = t.date_formatted || '—';
            elTime.textContent = t.time_formatted || '—';
            elDuration.textContent = t.duration || '—';
            elPrice.textContent = (parseInt(t.price || 0, 10) || 0) + ' ₽ / чел';
            elSeats.textContent = (parseInt(t.total_seats || 0, 10) || 0);
            elFree.textContent = (parseInt(t.free_seats || 0, 10) || 0);

            if (t.trainer_name && t.trainer_url) {
                elTrainer.innerHTML = '<a class="acc-link" href="' + t.trainer_url + '">' + t.trainer_name + '</a>';
            } else {
                elTrainer.textContent = t.trainer_name || '—';
            }

            if (t.room_name && t.room_url) {
                elRoom.innerHTML = '<a class="acc-link" href="' + t.room_url + '">' + t.room_name + '</a>';
            } else {
                elRoom.textContent = t.room_name || '—';
            }

            if (info) { info.classList.add('is-hidden'); info.textContent = ''; }
            if (formBook) formBook.classList.add('is-hidden');
            if (formCancel) formCancel.classList.add('is-hidden');
            if (formReq) formReq.classList.add('is-hidden');

            if (formBook) formBook.action = t.book_url || '#';
            if (formCancel) formCancel.action = t.cancel_url || '#';
            if (formReq) formReq.action = t.request_cancel_url || '#';

            if (t.is_cancelled) {
                if (info) {
                    info.classList.remove('is-hidden');
                    info.textContent = 'Тренировка отменена.';
                }
            } else if (t.is_booked_by_me) {
                formCancel.classList.remove('is-hidden');
                if (info) {
                    info.classList.remove('is-hidden');
                    info.textContent = 'Вы уже записаны на эту тренировку.';
                }
            } else if (t.has_other_training_at_same_time) {
                if (info) {
                    info.classList.remove('is-hidden');
                    info.textContent = 'У вас уже есть запись на другое занятие в это же время.';
                }
            } else if (parseInt(t.free_seats || 0, 10) > 0) {
                formBook.classList.remove('is-hidden');
            }

            if (formReq) {
                if (t.can_cancel_request && !t.is_cancelled && !t.has_pending_cancel) {
                    formReq.classList.remove('is-hidden');
                }
            }

            openModal();
        });
    });
})();
</script>
@endsection