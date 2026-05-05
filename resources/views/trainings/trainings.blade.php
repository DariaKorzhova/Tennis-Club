@extends('layouts.app')

@section('title', 'тренировки')

@section('content')
<div class="container">
    <h1>календарь тренировок</h1>

    @php
        $user = auth()->user();
        $isAdmin = $user && $user->isAdmin();
        $children = collect($user->children ?? []);

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

            <label class="form-label" for="type">тип:</label>
            <select name="type" id="type" class="form-select" onchange="this.form.submit()">
                <option value="all" {{ $selectedType == 'all' ? 'selected' : '' }}>все</option>
                @foreach($types as $key => $name)
                    <option value="{{ $key }}" {{ $selectedType == $key ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>

            <label class="form-label" for="room">помещение:</label>
            <select name="room" id="room" class="form-select" onchange="this.form.submit()">
                @foreach($rooms as $key => $name)
                    <option value="{{ $key }}" {{ $selectedRoom == $key ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>

            <label class="form-label" for="trainer">тренер:</label>
            <select name="trainer" id="trainer" class="form-select" onchange="this.form.submit()">
                @foreach($trainers as $key => $name)
                    <option value="{{ $key }}" {{ (string)$selectedTrainer === (string)$key ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>

            @if($user && $user->isUser())
                <label class="form-label" for="participant">участник:</label>
                <select name="participant" id="participant" class="form-select" onchange="this.form.submit()">
                    @php
                        $participantValue = $participant ?? ('user:' . (int) $user->id);
                    @endphp
                    <option value="user:{{ $user->id }}" {{ $participantValue === ('user:' . (int)$user->id) ? 'selected' : '' }}>
                        {{ $user->full_name }}
                    </option>
                    @foreach($children as $child)
                        <option value="child:{{ $child->id }}" {{ $participantValue === ('child:' . (int)$child->id) ? 'selected' : '' }}>
                            {{ $child->full_name ?? (($child->first_name ?? '') . ' ' . ($child->last_name ?? '')) }}
                        </option>
                    @endforeach
                </select>
            @endif

            <a href="{{ route('trainings.show', ['week' => $currentOffset]) }}" class="btn-secondary">сбросить</a>
        </form>

        @if($isAdmin)
            <div class="admin-add-under-filters">
                <a class="btn-admin-add" href="{{ route('admin.trainings.create') }}">добавить тренировку</a>
                <a class="btn-admin-cancel" href="{{ route('admin.cancellations') }}">запросы отмены</a>
            </div>
        @endif
    </div>

    <div class="calendar-wrap">
        @if(!$prevDisabled)
            <a class="calendar-arrow calendar-arrow--left"
               href="{{ route('trainings.show', ['week'=>$prevOffset,'type'=>$selectedType,'room'=>$selectedRoom,'trainer'=>$selectedTrainer]) }}"
               aria-label="предыдущие 7 дней">‹</a>
        @else
            <span class="calendar-arrow calendar-arrow--left is-disabled" aria-disabled="true">‹</span>
        @endif

        <div class="calendar-inner">
            <table class="calendar">
                <thead>
                <tr>
                    <th class="time-column">время</th>
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

                                                    if (!empty($t['is_my_court_booking'])) {
                                                        $bg = '#111111';
                                                    }
                                                @endphp

                                                <button
                                                    type="button"
                                                    class="training training--compact js-open-training {{ !empty($t['is_full']) ? 'training--full' : '' }} {{ !empty($t['is_booked_by_me']) || !empty($t['is_my_court_booking']) ? 'training--mine' : '' }}"
                                                    style="background-color: {{ $bg }};"
                                                    data-training='@json($t)'
                                                >
                                                    <div class="training-compact__top">
                                                        <div class="training-type">
                                                            <p>{{ $t['type_name'] }}</p>

                                                            @if(!empty($t['is_booked_by_me']))
                                                                <span class="training-booked-mark">вы записаны</span>
                                                            @elseif(!empty($t['is_my_court_booking']))
                                                                <span class="training-booked-mark">у вас аренда</span>
                                                            @elseif(!empty($t['has_court_booking_at_same_time']))
                                                                <span class="training-booked-mark">заняты арендой</span>
                                                            @endif
                                                        </div>
                                                        <div class="training-duration">{{ $t['duration'] }}</div>
                                                    </div>

                                                    <div class="training-compact__price">
                                                        @if(!empty($t['is_my_court_booking']))
                                                            {{ $t['room_name'] }}
                                                        @else
                                                            {{ (int)$t['price'] }} ₽ / чел
                                                        @endif
                                                    </div>
                                                </button>
                                            @endforeach
                                        @else
                                            <div class="empty-cell">-</div>
                                        @endif
                                    </div>

                                    @if($isAdmin && $cellDate)
                                        <a
                                            class="cell-add-wide"
                                            href="{{ route('admin.trainings.create', ['date' => $cellDate, 'time' => $cellTime]) }}"
                                            aria-label="добавить тренировку"
                                            title="добавить тренировку"
                                        >
                                            <span class="cell-add-wide__plus">+</span>
                                            <span class="cell-add-wide__text">добавить</span>
                                        </a>
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
               aria-label="следующие 7 дней">›</a>
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
            <div class="modal__title" id="trainingModalTitle">тренировка</div>
            <div class="modal__subtitle muted" id="trainingModalSubtitle"></div>
        </div>

        <div class="modal__body">
            <div class="modal-grid">
                <div class="modal-line"><span class="k">тип:</span> <span class="v" id="mType"></span></div>
                <div class="modal-line"><span class="k">дата:</span> <span class="v" id="mDate"></span></div>
                <div class="modal-line"><span class="k">время:</span> <span class="v" id="mTime"></span></div>
                <div class="modal-line"><span class="k">длительность:</span> <span class="v" id="mDuration"></span></div>
                <div class="modal-line"><span class="k">цена:</span> <span class="v" id="mPrice"></span></div>
                <div class="modal-line"><span class="k">мест:</span> <span class="v" id="mSeats"></span></div>
                <div class="modal-line"><span class="k">свободно:</span> <span class="v" id="mFree"></span></div>
                <div class="modal-line"><span class="k">тренер:</span> <span class="v" id="mTrainer"></span></div>
                <div class="modal-line"><span class="k">место:</span> <span class="v" id="mRoom"></span></div>

                @if($user && $user->isUser())
                    <div class="modal-line modal-line--column is-hidden" id="bookingTargetRow"></div>
                @endif

                <div class="modal-actions">
                    <form method="POST" action="#" id="formBook" class="is-hidden">
                        @csrf

                        @if($user && $user->isUser())
                            @php
                                $participantValue = $participant ?? ('user:' . (int) $user->id);
                                $pvType = 'user';
                                $pvId = (int) $user->id;
                                if (preg_match('/^(user|child):(\d+)$/', (string) $participantValue, $m)) {
                                    $pvType = $m[1];
                                    $pvId = (int) $m[2];
                                    if ($pvType === 'user') $pvId = (int) $user->id;
                                }
                            @endphp
                            <input type="hidden" name="bookable_type" id="bookable_type_hidden" value="{{ $pvType }}">
                            <input type="hidden" name="bookable_id" id="bookable_id_hidden" value="{{ $pvId }}">
                        @endif

                        <button type="submit" class="btn-card btn-card--success">записаться</button>
                    </form>

                    <form method="POST" action="#" id="formCancel" class="is-hidden" data-confirm="отменить запись на тренировку">
                        @csrf
                        <button type="submit" class="btn-card btn-card--danger">отменить запись</button>
                    </form>

                    <form method="POST" action="#" id="formTrainerRequest" class="is-hidden">
                        @csrf
                        <input class="input-mini" type="text" name="reason" placeholder="причина">
                        <button type="submit" class="btn-card btn-card--warning">запросить отмену</button>
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
    if (!modal) {
        return;
    }
    var overlay = modal.querySelector('.modal__overlay');

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

    var bookingTargetRow = document.getElementById('bookingTargetRow');
    var inputBookableType = document.getElementById('bookable_type_hidden');
    var inputBookableId = document.getElementById('bookable_id_hidden');
    var participantSelect = document.getElementById('participant');

    function syncParticipantToHidden() {
        if (!participantSelect || !inputBookableType || !inputBookableId) return;
        var v = participantSelect.value || '';
        var m = v.match(/^(user|child):(\d+)$/);
        if (!m) return;
        inputBookableType.value = m[1];
        inputBookableId.value = m[2];
    }

    if (participantSelect) {
        participantSelect.addEventListener('change', syncParticipantToHidden);
        syncParticipantToHidden();
    }

    function openModal() {
        modal.classList.remove('is-hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('no-scroll');

        var dialog = modal.querySelector('.modal__dialog');
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

    document.addEventListener('keydown', function (e) {
        if ((e.key === 'Escape' || e.key === 'escape') && !modal.classList.contains('is-hidden')) {
            closeModal();
        }
    });

    document.querySelectorAll('.js-open-training').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var t = {};
            try {
                t = JSON.parse(btn.getAttribute('data-training') || '{}');
            } catch (e) {}

            if (elType) elType.textContent = t.type_name || '—';
            if (elDate) elDate.textContent = t.date_formatted || '—';
            if (elTime) elTime.textContent = t.time_formatted || '—';
            if (elDuration) elDuration.textContent = t.duration || '—';
            if (elPrice) elPrice.textContent = (parseInt(t.price || 0, 10) || 0) + ' ₽ / чел';
            if (elSeats) elSeats.textContent = (parseInt(t.total_seats || 0, 10) || 0);
            if (elFree) elFree.textContent = (parseInt(t.free_seats || 0, 10) || 0);

            if (elTrainer) {
                if (t.trainer_name && t.trainer_url) {
                    elTrainer.innerHTML = '<a class="acc-link" href="' + t.trainer_url + '">' + t.trainer_name + '</a>';
                } else {
                    elTrainer.textContent = t.trainer_name || '—';
                }
            }

            if (elRoom) {
                if (t.room_name && t.room_url) {
                    elRoom.innerHTML = '<a class="acc-link" href="' + t.room_url + '">' + t.room_name + '</a>';
                } else {
                    elRoom.textContent = t.room_name || '—';
                }
            }

            if (info) {
                info.classList.add('is-hidden');
                info.textContent = '';
            }

            if (formBook) formBook.classList.add('is-hidden');
            if (formCancel) formCancel.classList.add('is-hidden');
            if (formReq) formReq.classList.add('is-hidden');
            if (bookingTargetRow) bookingTargetRow.classList.add('is-hidden');

            if (formBook) formBook.action = t.book_url || '#';
            if (formCancel) formCancel.action = t.cancel_url || '#';
            if (formReq) formReq.action = t.request_cancel_url || '#';
            syncParticipantToHidden();

            if (t.is_cancelled) {
                if (info) {
                    info.classList.remove('is-hidden');
                    info.textContent = 'тренировка отменена';
                }
            } else if (t.is_booked_by_me) {
                if (formCancel) formCancel.classList.remove('is-hidden');
                if (info) {
                    info.classList.remove('is-hidden');
                    info.textContent = 'вы уже записаны на эту тренировку';
                }
            } else if (t.is_my_court_booking) {
                if (info) {
                    info.classList.remove('is-hidden');
                    info.textContent = 'у вас уже есть аренда корта в это время';
                }
            } else if (t.has_court_booking_at_same_time) {
                if (info) {
                    info.classList.remove('is-hidden');
                    info.textContent = 'у вас уже есть аренда корта в это же время';
                }
            } else if (t.has_other_training_at_same_time) {
                if (info) {
                    info.classList.remove('is-hidden');
                    info.textContent = 'у вас уже есть запись на другое занятие в это же время';
                }
            } else if (parseInt(t.free_seats || 0, 10) > 0) {
                if (formBook) formBook.classList.remove('is-hidden');
            }

            if (formReq) {
                if (t.can_cancel_request && !t.is_cancelled && !t.has_pending_cancel) {
                    formReq.classList.remove('is-hidden');
                }
            }

            openModal();
        });
    });

    syncParticipantToHidden();
})();
</script>
@endsection