@extends('layouts.app')

@section('title', 'Аккаунт')

@section('content')
<div class="container">
    <div class="account-layout">

        <div class="account-content">
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

                $sortedTrainings = collect($trainings ?? [])->sortBy(function($t) {
                    $time = $t->time ? \Carbon\Carbon::parse($t->time)->format('H:i:s') : '00:00:00';
                    return ($t->date ?: '0000-00-00') . ' ' . $time;
                });

                $children = collect($user->children ?? [])->sortBy(function($child) {
                    return ($child->last_name ?? '') . ' ' . ($child->first_name ?? '');
                });

                $subscription = $user->activeSubscription ?? null;

                $statusClass = 'account-subscription-badge--pending';
                if ($subscription && $subscription->status === 'active') {
                    $statusClass = 'account-subscription-badge--active';
                } elseif ($subscription && $subscription->status === 'payment_overdue') {
                    $statusClass = 'account-subscription-badge--overdue';
                }

                $initials = mb_strtoupper(
                    mb_substr($user->first_name ?? '', 0, 1) . mb_substr($user->last_name ?? '', 0, 1)
                );
                $photoUrl = $user->photo_url ?? null;
            @endphp

            {{-- АККАУНТ --}}
            <section id="account-info" class="account-panel is-active">
                <h2>Аккаунт</h2>

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
                            <div class="v">
                                @if(!empty($user->birth_date))
                                    {{ \Carbon\Carbon::parse($user->birth_date)->format('d.m.Y') }}
                                @else
                                    —
                                @endif
                            </div>
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
            </section>

            {{-- МОЙ АБОНЕМЕНТ --}}
            <section id="account-subscription" class="account-panel">
                <h2>Мой абонемент</h2>

                @if(!$subscription)
                    <div class="account-subscription-card">
                        <div class="muted">Активного абонемента нет.</div>

                        <div class="account-subscription-actions">
                            <a href="{{ route('subscriptions.choose') }}" class="account-edit-btn">Оформить абонемент</a>
                        </div>
                    </div>
                @else
                    <div class="account-subscription-card">
                        <div class="account-subscription-actions" style="margin-top:0; margin-bottom:8px;">
                            <span class="account-subscription-badge {{ $statusClass }}">
                                {{ $subscription->status_label }}
                            </span>
                        </div>

                        <div class="account-subscription-grid">
                            <div class="account-subscription-item">
                                <span class="k">Тариф</span>
                                <span class="v">{{ optional($subscription->plan)->name ?? '—' }}</span>
                            </div>

                            <div class="account-subscription-item">
                                <span class="k">Способ оплаты</span>
                                <span class="v">{{ $subscription->payment_mode_label ?? '—' }}</span>
                            </div>

                            <div class="account-subscription-item">
                                <span class="k">Действует до</span>
                                <span class="v">{{ optional($subscription->end_date)->format('d.m.Y') }}</span>
                            </div>

                            @if(!empty($subscription->next_payment_date))
                                <div class="account-subscription-item">
                                    <span class="k">Следующий платёж</span>
                                    <span class="v">{{ $subscription->next_payment_date->format('d.m.Y') }}</span>
                                </div>
                            @endif

                            @if(!is_null($subscription->visits_left))
                                <div class="account-subscription-item">
                                    <span class="k">Осталось посещений</span>
                                    <span class="v">{{ $subscription->visits_left }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="account-subscription-actions">
                            <a href="{{ route('subscriptions.history') }}" class="account-edit-btn">История платежей</a>
                            <a href="{{ route('subscriptions.choose', ['mode' => 'renew']) }}" class="account-edit-btn">Продлить абонемент</a>
                        </div>
                    </div>
                @endif
            </section>

            {{-- МОИ ДЕТИ --}}
            <section id="account-children" class="account-panel">
                <h2>Мои дети</h2>

                @if($children->isEmpty())
                    <div class="account-subscription-card">
                        <div class="muted">В личный кабинет пока не добавлены дети.</div>

                        <div class="account-subscription-actions">
                            <a href="{{ route('account.children.create') }}" class="account-edit-btn">Добавить ребёнка</a>
                        </div>
                    </div>
                @else
                    <div class="account-list">
                        @foreach($children as $child)
                            @php
                                $childSub = $child->activeSubscription ?? null;

                                $childStatusClass = 'account-subscription-badge--pending';
                                if ($childSub && $childSub->status === 'active') {
                                    $childStatusClass = 'account-subscription-badge--active';
                                } elseif ($childSub && $childSub->status === 'payment_overdue') {
                                    $childStatusClass = 'account-subscription-badge--overdue';
                                }
                            @endphp

                            <div class="account-training account-training--colored" style="background-color:#2e9b00;">
                                <div class="acc-top">
                                    <div class="acc-type">{{ $child->full_name ?? (($child->first_name ?? '') . ' ' . ($child->last_name ?? '')) }}</div>
                                    <div class="acc-dt">
                                        @if(!empty($child->birth_date))
                                            {{ \Carbon\Carbon::parse($child->birth_date)->format('d.m.Y') }}
                                        @else
                                            Дата рождения не указана
                                        @endif
                                    </div>
                                </div>

                                <div class="acc-mini">
                                    <div><strong>Возраст:</strong> {{ $child->age ?? '—' }}</div>
                                    <div><strong>Уровень:</strong> {{ $child->level ?? '—' }}</div>
                                </div>

                                @if($childSub)
                                    <div class="account-subscription-actions" style="margin-top:10px; margin-bottom:8px;">
                                        <span class="account-subscription-badge {{ $childStatusClass }}">
                                            {{ $childSub->status_label ?? 'активен' }}
                                        </span>
                                    </div>

                                    <div class="acc-mini">
                                        <div><strong>Абонемент:</strong> {{ optional($childSub->plan)->name ?? '—' }}</div>
                                        <div><strong>Действует до:</strong> {{ optional($childSub->end_date)->format('d.m.Y') ?? '—' }}</div>
                                        @if(!is_null($childSub->visits_left))
                                            <div><strong>Осталось посещений:</strong> {{ $childSub->visits_left }}</div>
                                        @endif
                                    </div>
                                @else
                                    <div class="muted">У ребёнка пока нет активного абонемента.</div>
                                @endif

                                <div class="account-subscription-actions">
                                    <a href="{{ route('account.children.edit', $child->id) }}" class="account-edit-btn">Редактировать</a>

                                    @if(Route::has('account.children.subscriptions'))
                                        <a href="{{ route('account.children.subscriptions', $child->id) }}" class="account-edit-btn">Абонементы</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="account-subscription-actions">
                        <a href="{{ route('account.children.create') }}" class="account-edit-btn">Добавить ещё ребёнка</a>
                    </div>
                @endif
            </section>

            {{-- МОИ ТРЕНИРОВКИ --}}
            <section id="account-trainings" class="account-panel">
                <h2>Мои тренировки</h2>

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
                                $room = $t->rooms->first() ?? null;

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
                                        <form method="POST" action="{{ route('trainings.cancel', $t->id) }}" data-confirm="Отменить запись на тренировку?">
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
            </section>

            {{-- АРЕНДА --}}
            @if($user->isUser())
                <section id="account-courts" class="account-panel">
                    <h2>Моя аренда кортов</h2>

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
                                        <div class="acc-dt">{{ $dateLabel }} {{ $timeLabel }}</div>
                                    </div>

                                    <div class="acc-mini">
                                        <div><strong>Корт:</strong> {{ optional($booking->room)->name ?? '—' }}</div>
                                        <div><strong>Часов:</strong> {{ $booking->hours_count ?? 1 }}</div>
                                        <div><strong>Человек:</strong> {{ $booking->persons ?? 1 }}</div>
                                        <div><strong>Стоимость:</strong> {{ (int)($booking->total_price ?? $booking->price) }} ₽</div>
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
                                                <button class="btn-card btn-card--warning" type="submit">Изменить</button>
                                            </div>
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route('account.court-bookings.cancel', $groupKey) }}" data-confirm="Отменить аренду корта?">
                                        @csrf
                                        <button class="btn-card btn-card--danger" type="submit">Отменить аренду</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        </div>

        <aside class="account-sidebar">
            <div class="account-sidebar__title">Личный кабинет</div>

            <button type="button" class="account-sidebar__link js-account-tab is-active" data-tab="account-info">
                Аккаунт
            </button>

            <button type="button" class="account-sidebar__link js-account-tab" data-tab="account-children">
                Мои дети
            </button>

            <button type="button" class="account-sidebar__link js-account-tab" data-tab="account-trainings">
                Тренировки
            </button>

            @if($user->isUser())
                <button type="button" class="account-sidebar__link js-account-tab" data-tab="account-courts">
                    Аренда
                </button>
            @endif

            <button type="button" class="account-sidebar__link js-account-tab" data-tab="account-subscription">
                Абонемент
            </button>
        </aside>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.js-account-tab');
    const panels = document.querySelectorAll('.account-panel');

    function openTab(tabId) {
        tabButtons.forEach(function (btn) {
            btn.classList.toggle('is-active', btn.getAttribute('data-tab') === tabId);
        });

        panels.forEach(function (panel) {
            panel.classList.toggle('is-active', panel.id === tabId);
        });

        try {
            localStorage.setItem('accountActiveTab', tabId);
        } catch (e) {}
    }

    tabButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            openTab(btn.getAttribute('data-tab'));
        });
    });

    let initialTab = 'account-info';

    try {
        const saved = localStorage.getItem('accountActiveTab');
        if (saved && document.getElementById(saved)) {
            initialTab = saved;
        }
    } catch (e) {}

    openTab(initialTab);
});
</script>
@endsection