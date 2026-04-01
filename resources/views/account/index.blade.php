@extends('layouts.app')

@section('title', 'Аккаунт')

@section('content')
<div class="container">
    <h2>Аккаунт</h2>

    @php
        $typeColors = [
            'individual' => '#996016',
            'split'      => '#5f9414',
            'kids'       => '#2e9b00',
            'group'      => '#18a000',
            'fitness'    => '#2196F3',
            'yoga'       => '#9C27B0',
            'massage'    => '#FF9800',
        ];

        $typeNames = [
            'individual' => 'Индивидуальная',
            'split'      => 'Сплит',
            'kids'       => 'Детская',
            'group'      => 'Групповая',
            'fitness'    => 'Фитнес',
            'yoga'       => 'Йога',
            'massage'    => 'Массаж',
        ];

        $sortedTrainings = $trainings->sortBy(function($t) {
            $time = $t->time ? \Carbon\Carbon::parse($t->time)->format('H:i:s') : '00:00:00';
            return ($t->date ?: '0000-00-00') . ' ' . $time;
        });

        $initials = mb_strtoupper(
            mb_substr($user->first_name, 0, 1) . mb_substr($user->last_name, 0, 1)
        );
        $photoUrl = $user->photo_url ?? null;
    @endphp

    <div class="account-profile">
        <div class="account-photo">
            @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="Фото профиля">
            @else
                <div class="account-photo__placeholder">{{ $initials }}</div>
            @endif
        </div>

        <div class="account-info">
            <div class="account-info__row">
                <div class="k">Имя</div>
                <div class="v">{{ $user->full_name }}</div>
            </div>

            <div class="account-info__row">
                <div class="k">Email</div>
                <div class="v">{{ $user->email }}</div>
            </div>

            <div class="account-info__row">
                <div class="k">Дата рождения</div>
                <div class="v">{{ \Carbon\Carbon::parse($user->birth_date)->format('d.m.Y') }}</div>
            </div>

            @if($user->isTrainer())
                <div class="account-info__row">
                    <div class="k">Специализация</div>
                    <div class="v">{{ $user->specialization_name }}</div>
                </div>
            @endif
        </div>

        <div class="account-actions">
            <a href="{{ route('account.edit') }}" class="account-edit-btn">Редактировать</a>
        </div>
    </div>

    <h2 class="section-title">Мои тренировки</h2>

    @if($sortedTrainings->isEmpty())
        <div class="muted">
            @if($user->isTrainer())
                У вас пока нет назначенных тренировок.
            @else
                Вы пока ни на что не записаны.
            @endif
        </div>
    @else
        <div class="account-list">
            @foreach($sortedTrainings as $t)
                @php
                    $room = $t->rooms->first();

                    $bg = $typeColors[$t->type] ?? '#777777';
                    $typeLabel = $typeNames[$t->type] ?? $t->type;

                    $dateLabel = $t->date ? \Carbon\Carbon::parse($t->date)->format('d.m.Y') : '—';
                    $timeLabel = $t->time ? \Carbon\Carbon::parse($t->time)->format('H:i') : '—';

                    $price = (int)($t->price ?? ($t->pivot->price ?? 0));
                    $pivotStatus = $t->pivot->status ?? null;
                    $isCancelledTraining = !empty($t->is_cancelled);
                    $isCancelledBooking = ($user->isUser() && $pivotStatus && $pivotStatus !== 'active');
                @endphp

                <div class="account-training account-training--colored" style="background-color: {{ $bg }};">
                    <div class="acc-top">
                        <div class="acc-type">{{ $typeLabel }}</div>
                        <div class="acc-dt">{{ $dateLabel }} {{ $timeLabel }}</div>
                    </div>

                    <div class="acc-mini">
                        <div><strong>Длительность:</strong> {{ $t->duration }}</div>
                        <div><strong>Цена:</strong> {{ $price }} ₽ / чел</div>
                    </div>

                    <div class="acc-links">
                        <div>
                            <strong>Тренер:</strong>
                            @if($t->trainer)
                                <a class="acc-link" href="{{ route('trainers.show', $t->trainer->id) }}">{{ $t->trainer->full_name }}</a>
                            @else
                                <span class="acc-muted">Не назначен</span>
                            @endif
                        </div>

                        <div>
                            <strong>Место:</strong>
                            @if($room)
                                <a class="acc-link" href="{{ route('rooms.view', $room->id) }}">{{ $room->name }}</a>
                            @else
                                <span class="acc-muted">Не указано</span>
                            @endif
                        </div>
                    </div>

                    @if($isCancelledTraining)
                        <div class="muted">Тренировка отменена администратором.</div>
                    @elseif($isCancelledBooking)
                        <div class="muted">Запись отменена.</div>
                    @endif

                    @if(!$isCancelledTraining)
                        @if($user->isUser() && !$isCancelledBooking)
                            <form method="POST" action="{{ route('trainings.cancel', $t->id) }}">
                                @csrf
                                <button class="btn-card btn-card--danger" type="submit">Отменить запись</button>
                            </form>
                        @endif

                        @if($user->isTrainer() && (int)$t->trainer_id === (int)$user->id)
                            @if(!empty($t->has_pending_cancel))
                                <div class="muted">Заявка на отмену отправлена</div>
                            @else
                                <form method="POST"
                                      action="{{ route('trainings.request_cancel', $t->id) }}"
                                      class="trainer-cancel-row">
                                    @csrf
                                    <input class="input-mini" type="text" name="reason" placeholder="Причина">
                                    <button class="btn-card btn-card--warning" type="submit">Запросить отмену</button>
                                </form>
                            @endif
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if($user->isUser())
        <h2 class="section-title">Моя аренда кортов</h2>

        @if(empty($courtBookings) || $courtBookings->isEmpty())
            <div class="muted">У вас пока нет арендованных кортов.</div>
        @else
            <div class="account-list">
                @foreach($courtBookings as $booking)
                    @php
                        $dateLabel = \Carbon\Carbon::parse($booking->date)->format('d.m.Y');
                        $timeLabel = \Carbon\Carbon::parse($booking->time)->format('H:i');
                        $groupKey = $booking->booking_group ?: $booking->id;
                    @endphp

                    <div class="account-training account-training--colored" style="background-color:#222;">
                        <div class="acc-top">
                            <div class="acc-type">Аренда корта</div>
                            <div class="acc-dt">{{ $dateLabel }}      {{ $timeLabel }}</div>
                        </div>

                        <div class="acc-mini">
                            <div><strong>Корт:</strong> {{ optional($booking->room)->name ?? '—' }}</div>
                            <div><strong>Часов:</strong> {{ $booking->hours_count ?? 1 }}</div>
                            <div><strong>Человек:</strong> {{ $booking->persons ?? 1 }}</div>
                            <div><strong>Стоимость:</strong> {{ (int)($booking->total_price ?? $booking->price) }} ₽</div>
                            <p><strong>Количество человек:</strong></p>
                        </div>

                        <form method="POST" action="{{ route('account.court-bookings.update-persons', $groupKey) }}">
                            @csrf
                            <div class="btn-row">
            
                                <div>
                                    <input type="number"
                                           class="input-mini"
                                           name="persons"
                                           min="1"
                                           max="4"
                                           value="{{ $booking->persons ?? 1 }}">
                                </div>
                                <div>
                                    <button class="btn-card btn-card--warning" type="submit">изменить</button>
                                </div>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('account.court-bookings.cancel', $groupKey) }}">
                            @csrf
                            <button class="btn-card btn-card--danger" type="submit">Отменить аренду</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
@endsection