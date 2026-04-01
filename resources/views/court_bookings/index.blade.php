@extends('layouts.app')

@section('title', 'Аренда кортов')

@section('content')
<div class="container">
    <h1>Аренда кортов</h1>

    @php
        $currentOffset = (int)($dayOffset ?? 0);
        if ($currentOffset < 0) $currentOffset = 0;
        if ($currentOffset > 3) $currentOffset = 3;

        $prevOffset = max(0, $currentOffset - 1);
        $nextOffset = min(3, $currentOffset + 1);

        $prevDisabled = ($currentOffset === 0);
        $nextDisabled = ($currentOffset === 3);
    @endphp

    <div class="filter-section">
        <form method="GET" action="{{ route('court-rent.index') }}" class="row" id="courtFilterForm">
            <input type="hidden" name="week" value="{{ $currentOffset }}">

            <label class="form-label" for="room_id">Корт:</label>
            <select name="room_id" id="room_id" class="form-select" onchange="this.form.submit()">
                @foreach($courts as $court)
                    <option value="{{ $court->id }}" {{ (int)$selectedCourtId === (int)$court->id ? 'selected' : '' }}>
                        {{ $court->name }}
                    </option>
                @endforeach
            </select>

            <a href="{{ route('court-rent.index', ['week' => $currentOffset, 'room_id' => $selectedCourtId]) }}" class="btn-secondary">
                Сбросить
            </a>
        </form>
    </div>

    <div class="calendar-wrap">
        @if(!$prevDisabled)
            <a class="calendar-arrow calendar-arrow--left"
               href="{{ route('court-rent.index', ['week' => $prevOffset, 'room_id' => $selectedCourtId]) }}">‹</a>
        @else
            <span class="calendar-arrow calendar-arrow--left is-disabled">‹</span>
        @endif

        <div class="calendar-inner">
            <table class="calendar">
                <thead>
                <tr>
                    <th class="time-column">Время</th>
                    @foreach($calendarData['days'] as $day)
                        <th class="{{ $day['isToday'] ? 'is-today' : '' }}">
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
                                $cell = $calendarData['grid'][$day['date']][$time] ?? null;
                            @endphp

                            <td class="{{ $day['isToday'] ? 'is-today' : '' }}">
                                @if($cell && $cell['status'] === 'free')
                                    <button
                                        type="button"
                                        class="btn-card btn-card--success js-open-court-booking"
                                        data-room-id="{{ $cell['room_id'] }}"
                                        data-room-name="{{ $cell['room_name'] }}"
                                        data-date="{{ $cell['date'] }}"
                                        data-time="{{ $cell['time'] }}"
                                        data-price-hour="{{ $pricePerHour }}"
                                    >
                                        Записаться
                                    </button>
                                @elseif($cell && $cell['status'] === 'booked')
                                    <div class="court-slot court-slot--booked"></div>
                                @elseif($cell && $cell['status'] === 'training')
                                    <div class="court-slot court-slot--training"></div>
                                @else
                                    <div class="court-slot"></div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        @if(!$nextDisabled)
            <a class="calendar-arrow calendar-arrow--right"
               href="{{ route('court-rent.index', ['week' => $nextOffset, 'room_id' => $selectedCourtId]) }}">›</a>
        @else
            <span class="calendar-arrow calendar-arrow--right is-disabled">›</span>
        @endif
    </div>
</div>

<div class="modal is-hidden" id="courtBookingModal" aria-hidden="true">
    <div class="modal__overlay" data-close-court="1"></div>

    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="courtBookingTitle">
        <button class="modal__close" type="button" data-close-court="1">×</button>

        <div class="modal__header">
            <div class="modal__title" id="courtBookingTitle">Бронирование корта</div>
            <div class="modal__subtitle muted" id="courtBookingSubtitle"></div>
        </div>

        <div class="modal__body">
            <form method="POST" action="{{ route('court-rent.store') }}" id="courtBookingForm">
                @csrf

                <input type="hidden" name="room_id" id="cbRoomId">
                <input type="hidden" name="date" id="cbDate">
                <input type="hidden" name="time" id="cbTime">

                <div class="modal-grid">
                    <div class="modal-line"><span class="k">Корт:</span> <span class="v" id="cbRoomName"></span></div>
                    <div class="modal-line"><span class="k">Дата:</span> <span class="v" id="cbDateText"></span></div>
                    <div class="modal-line"><span class="k">Время:</span> <span class="v" id="cbTimeText"></span></div>
                    <div class="modal-line"><span class="k">Цена в час:</span> <span class="v" id="cbPriceHour"></span></div>

                    <div class="field" style="grid-column: 1 / -1;">
                        <label class="lbl" for="cbPersons">Количество человек</label>
                        <select class="inp" name="persons" id="cbPersons" required>
                            <option value="1">1 человек</option>
                            <option value="2">2 человека</option>
                            <option value="3">3 человека</option>
                            <option value="4">4 человека</option>
                        </select>
                    </div>

                    <div class="field" style="grid-column: 1 / -1;">
                        <label class="lbl" for="cbHours">Сколько часов</label>
                        <select class="inp" name="hours" id="cbHours" required>
                            <option value="1">1 час</option>
                            <option value="2">2 часа</option>
                        </select>
                    </div>

                    <div class="modal-line" style="grid-column: 1 / -1;">
                        <span class="k">Итого:</span>
                        <span class="v" id="cbTotalPrice">2000 ₽</span>
                    </div>

                    <div class="modal-actions" style="grid-column: 1 / -1;">
                        <button type="submit" class="btn-card btn-card--success">Забронировать</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('courtBookingModal');
    if (!modal) return;

    var cbRoomId = document.getElementById('cbRoomId');
    var cbDate = document.getElementById('cbDate');
    var cbTime = document.getElementById('cbTime');

    var cbRoomName = document.getElementById('cbRoomName');
    var cbDateText = document.getElementById('cbDateText');
    var cbTimeText = document.getElementById('cbTimeText');
    var cbPriceHour = document.getElementById('cbPriceHour');
    var cbHours = document.getElementById('cbHours');
    var cbTotalPrice = document.getElementById('cbTotalPrice');

    var currentPriceHour = 2000;

    function openModal() {
        modal.classList.remove('is-hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('no-scroll');

        var dialog = modal.querySelector('.modal__dialog');
        if (dialog) dialog.scrollTop = 0;

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function closeModal() {
        modal.classList.add('is-hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('no-scroll');
    }

    function updateTotal() {
        var hours = parseInt(cbHours.value || '1', 10);
        cbTotalPrice.textContent = (currentPriceHour * hours) + ' ₽';
    }

    modal.querySelectorAll('[data-close-court="1"]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('is-hidden')) {
            closeModal();
        }
    });

    cbHours.addEventListener('change', updateTotal);

    document.querySelectorAll('.js-open-court-booking').forEach(function (btn) {
        btn.addEventListener('click', function () {
            currentPriceHour = parseInt(btn.getAttribute('data-price-hour') || '2000', 10);

            cbRoomId.value = btn.getAttribute('data-room-id') || '';
            cbDate.value = btn.getAttribute('data-date') || '';
            cbTime.value = (btn.getAttribute('data-time') || '') + ':00';

            cbRoomName.textContent = btn.getAttribute('data-room-name') || '—';
            cbDateText.textContent = btn.getAttribute('data-date') || '—';
            cbTimeText.textContent = btn.getAttribute('data-time') || '—';
            cbPriceHour.textContent = currentPriceHour + ' ₽';

            cbHours.value = '1';
            updateTotal();
            openModal();
        });
    });
})();
</script>
@endsection