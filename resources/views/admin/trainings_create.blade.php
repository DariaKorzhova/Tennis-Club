@extends('layouts.app')

@section('title', 'добавить тренировку')

@section('content')
<div class="container">
    <h2>добавить тренировку</h2>

    <div class="admin-form-shell">
        <form method="POST" action="{{ route('admin.trainings.store') }}" class="admin-form" id="adminTrainingForm">
            @csrf

            <div class="admin-grid">
                <div class="field">
                    <label class="lbl">дата</label>
                    <input class="inp inp-date" type="date" name="date" id="atDate" required value="{{ old('date', $prefillDate ?? '') }}">
                </div>

                <div class="field">
                    <label class="lbl">время</label>
                    <select class="inp" name="time" id="atTime" required>
                        @foreach($timeOptions as $t)
                            <option value="{{ $t }}" {{ old('time', $prefillTime ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                    <div class="hint">список будет фильтроваться по занятости тренера/помещения.</div>
                </div>

                <div class="field">
                    <label class="lbl">тип тренировки</label>
                    <select class="inp" name="type" id="typeSelect" required>
                        @foreach($types as $k => $v)
                            <option value="{{ $k }}" {{ old('type') === $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label class="lbl">длительность</label>
                    <select class="inp" name="duration" required>
                        @foreach($durationOptions as $d)
                            <option value="{{ $d }}" {{ old('duration') === $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label class="lbl">помещение</label>
                    <select class="inp" name="room_id" id="roomSelect" required>
                        @foreach($rooms as $r)
                            <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                    <div class="hint">показываются только помещения, подходящие типу и свободные на выбранные дату/время.</div>
                </div>

                <div class="field">
                    <label class="lbl">тренер (обязательно)</label>
                    <select class="inp" name="trainer_id" id="trainerSelect" required>
                        <option value="" disabled selected>выберите тренера</option>
                        @foreach($trainers as $tr)
                            <option value="{{ $tr->id }}">
                                {{ $tr->name }} ({{ $tr->specialization_name }})
                            </option>
                        @endforeach
                    </select>
                    <div class="hint" id="trainerHint">тренер будет отфильтрован под тип тренировки и занятость.</div>
                </div>

                <div class="field">
                    <label class="lbl">кол-во мест</label>
                    <input class="inp" type="number" name="persons" id="personsInput" min="1" max="200" required value="{{ old('persons', 6) }}">
                    <div class="hint" id="personsHint">ограничения зависят от типа.</div>
                </div>

                <div class="field">
                    <label class="lbl">цена за 1 человека (минимум 1000)</label>
                    <input class="inp" type="number" name="price" id="priceInput" min="1000" step="50" required value="{{ old('price', 1000) }}">
                    <div class="hint">цена подтягивается из настроек типа тренировки.</div>
                </div>
            </div>

            <div class="admin-actions">
                <button class="btn-save" type="submit">сохранить</button>
                <a class="btn-cancel" href="{{ route('trainings.show') }}">назад</a>
            </div>

        </form>
    </div>
</div>

<script>
(function () {
    var typeSelect = document.getElementById('typeSelect');
    var trainerSelect = document.getElementById('trainerSelect');
    var personsInput = document.getElementById('personsInput');
    var personsHint = document.getElementById('personsHint');
    var dateEl = document.getElementById('atDate');
    var timeEl = document.getElementById('atTime');
    var roomEl = document.getElementById('roomSelect');
    var priceInput = document.getElementById('priceInput');

    var availabilityUrl = "{{ route('admin.trainings.availability') }}";

    function setOptions(select, items, currentValue) {
        select.innerHTML = '';
        items.forEach(function (it) {
            var opt = document.createElement('option');
            opt.value = String(it.value);
            opt.textContent = it.label;
            select.appendChild(opt);
        });
        if (currentValue) {
            var exists = Array.from(select.options).some(function (o) { return o.value === String(currentValue); });
            if (exists) select.value = String(currentValue);
        }
    }

    function applyPersonsRules(rules) {
        personsInput.removeAttribute('readonly');
        personsInput.classList.remove('is-locked');

        personsInput.min = rules.min;
        personsInput.max = rules.max;

        if (rules.fixed !== null && rules.fixed !== undefined) {
            personsInput.value = rules.fixed;
            personsInput.setAttribute('readonly', 'readonly');
            personsInput.classList.add('is-locked');
            personsHint.textContent = 'для выбранного типа мест фиксировано: ' + rules.fixed;
        } else {
            if (!personsInput.value) personsInput.value = rules.min;
            if (parseInt(personsInput.value, 10) < parseInt(rules.min, 10)) personsInput.value = rules.min;
            if (parseInt(personsInput.value, 10) > parseInt(rules.max, 10)) personsInput.value = rules.max;
            personsHint.textContent = 'допустимо мест: от ' + rules.min + ' до ' + rules.max;
        }
    }

    var lastReq = 0;
    function refresh() {
        if (!dateEl.value) return;

        var q = new URLSearchParams();
        q.set('date', dateEl.value);
        q.set('time', timeEl.value);
        q.set('type', typeSelect.value);
        if (roomEl.value) q.set('room_id', roomEl.value);
        if (trainerSelect.value) q.set('trainer_id', trainerSelect.value);

        var myReq = ++lastReq;

        fetch(availabilityUrl + '?' + q.toString(), { headers: { 'accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (myReq !== lastReq) return;
                if (!data || !data.ok) return;

                var keepRoom = roomEl.value;
                var keepTrainer = trainerSelect.value;
                var keepTime = timeEl.value;

                var roomItems = (data.rooms || []).map(function (r) { return ({ value: r.id, label: r.name }); });
                if (!roomItems.length) {
                    roomItems = [{ value: '', label: 'нет доступных помещений' }];
                    roomEl.disabled = true;
                } else {
                    roomEl.disabled = false;
                }
                setOptions(roomEl, roomItems, keepRoom);
                if (roomEl.disabled) roomEl.value = '';

                var trainerItems = (data.trainers || []).map(function (t) {
                    return ({ value: t.id, label: t.name + ' (' + (t.specialization_name || t.specialization || '') + ')' });
                });
                if (!trainerItems.length) {
                    trainerItems = [{ value: '', label: 'нет доступных тренеров' }];
                    trainerSelect.disabled = true;
                } else {
                    trainerSelect.disabled = false;
                }
                setOptions(trainerSelect, trainerItems, keepTrainer);
                if (trainerSelect.disabled) trainerSelect.value = '';

                var timeItems = (data.timeOptions || []).map(function (t) { return ({ value: t, label: t }); });
                if (!timeItems.length) {
                    timeItems = [{ value: keepTime, label: keepTime + ' (занято)' }];
                    timeEl.disabled = true;
                } else {
                    timeEl.disabled = false;
                }
                setOptions(timeEl, timeItems, keepTime);

                applyPersonsRules(data.persons);
                if (typeof data.price !== 'undefined' && priceInput) {
                    priceInput.value = data.price;
                }
            })
            .catch(function () {});
    }

    typeSelect.addEventListener('change', function () {
        roomEl.value = '';
        trainerSelect.value = '';
        refresh();
    });
    dateEl.addEventListener('change', refresh);
    timeEl.addEventListener('change', refresh);
    roomEl.addEventListener('change', refresh);
    trainerSelect.addEventListener('change', refresh);

    refresh();
})();
</script>
@endsection
