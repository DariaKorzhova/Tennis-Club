@extends('layouts.app')

@section('title', 'аккаунт')

@section('content')
<div class="container">
    <div class="account-layout">

        <div class="account-content">
            @if ($errors->any())
                <div class="form-error" style="margin-bottom:14px;">
                    @foreach ($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            @php
                $typeColors = [
                    'individual' => '#996016',
                    'split'      => '#5f9414',
                    'kids'       => '#2e9b00',
                    'group'      => '#18a000',
                    'fitness'    => '#2196f3',
                    'yoga'       => '#9c27b0',
                    'massage'    => '#ff9800',
                ];

                $typeNames = [
                    'individual' => 'индивидуальная',
                    'split'      => 'сплит',
                    'kids'       => 'детская',
                    'group'      => 'групповая',
                    'fitness'    => 'фитнес',
                    'yoga'       => 'йога',
                    'massage'    => 'массаж',
                ];

                $sortedTrainings = collect($trainings ?? [])->sortBy(function ($t) {
                    $time = $t->time ? \Carbon\Carbon::parse($t->time)->format('H:i:s') : '00:00:00';
                    return ($t->date ?: '0000-00-00') . ' ' . $time;
                });

                $participantTrainingGroups = collect($participantTrainingGroups ?? [])->map(function ($group) {
                    $group['trainings'] = collect($group['trainings'] ?? []);
                    return $group;
                });

                $children = collect($user->children ?? [])->sortBy(function ($child) {
                    return ($child->last_name ?? '') . ' ' . ($child->first_name ?? '');
                });

                $subscription = $user->activeSubscription ?? null;
                if ($subscription) {
                    $subscription->loadMissing(['plan', 'payments']);
                }
                $showInstallmentPay = $subscription
                    && $subscription->status === 'active'
                    && $subscription->payment_mode === 'installment'
                    && $subscription->remainingPlanAmount() > 0;

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

            {{-- аккаунт --}}
            <section id="account-info" class="account-panel is-active">
                <h2>аккаунт</h2>

                <div class="account-profile">
                    <div class="account-photo">
                        @if($photoUrl)
                            <img src="{{ $photoUrl }}" alt="фото профиля">
                        @else
                            <div class="account-photo__placeholder">{{ $initials }}</div>
                        @endif
                    </div>

                    <div class="account-info">
                        <div class="account-info__row">
                            <div class="k">имя</div>
                            <div class="v">{{ $user->full_name }}</div>
                        </div>

                        <div class="account-info__row">
                            <div class="k">Email</div>
                            <div class="v">{{ $user->email }}</div>
                        </div>

                        <div class="account-info__row">
                            <div class="k">дата рождения</div>
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
                                <div class="k">специализация</div>
                                <div class="v">{{ $user->specialization_name }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="account-actions">
                        <a href="{{ route('account.edit') }}" class="account-edit-btn">редактировать</a>
                    </div>
                </div>
            </section>

            {{-- мой абонемент --}}
            <section id="account-subscription" class="account-panel">
                <h2>мой абонемент</h2>

                @if(!$subscription)
                    <div class="account-subscription-card">
                        <div class="muted">активного абонемента нет.</div>

                        <div class="account-subscription-actions">
                            <a href="{{ route('subscriptions.choose') }}" class="account-edit-btn">оформить абонемент</a>
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
                                <span class="k">тариф</span>
                                <span class="v">{{ optional($subscription->plan)->name ?? '—' }}</span>
                            </div>

                            <div class="account-subscription-item">
                                <span class="k">способ оплаты</span>
                                <span class="v">{{ $subscription->payment_mode_label ?? '—' }}</span>
                            </div>

                            <div class="account-subscription-item">
                                <span class="k">действует до</span>
                                <span class="v">{{ optional($subscription->end_date)->format('d.m.Y') }}</span>
                            </div>

                            @if(!empty($subscription->next_payment_date))
                                <div class="account-subscription-item">
                                    <span class="k">следующий платёж</span>
                                    <span class="v">{{ $subscription->next_payment_date->format('d.m.Y') }}</span>
                                </div>
                            @endif

                            @if(!is_null($subscription->visits_left))
                                <div class="account-subscription-item">
                                    <span class="k">осталось посещений</span>
                                    <span class="v">{{ $subscription->visits_left }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="account-subscription-actions">
                            <a href="{{ route('subscriptions.history', ['subscription_id' => $subscription->id]) }}" class="account-edit-btn">история платежей</a>
                            @if($showInstallmentPay)
                                <button type="button"
                                        class="account-installment-pay-btn js-installment-pay-open"
                                        data-subscription-id="{{ $subscription->id }}"
                                        data-suggested="{{ $subscription->suggestedInstallmentNextPaymentAmount() }}"
                                        data-max="{{ $subscription->remainingPlanAmount() }}">
                                    внести платёж
                                </button>
                            @endif
                        </div>

                        
                    </div>
                    <div class="account-subscription-actions" style="margin-top:10px;">
                            <a href="{{ route('subscriptions.choose') }}" class="account-secondary-btn">выбрать абонемент</a>
                        </div>
                @endif
            </section>

            {{-- мои дети --}}
            <section id="account-children" class="account-panel">
                <h2>мои дети</h2>

                @if($children->isEmpty())
                    <div class="account-subscription-card">
                        <div class="muted">в личный кабинет пока не добавлены дети</div>

                        <div class="account-subscription-actions">
                            <a href="{{ route('account.children.create') }}" class="account-edit-btn">добавить ребёнка</a>
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
                            <div style="max-width: 300px;">
                               <div class="account-training account-training--colored" style="background-color:#2e9b00;">
                                <div class="acc-top">
                                    <div class="acc-type">{{ $child->full_name ?? (($child->first_name ?? '') . ' ' . ($child->last_name ?? '')) }}</div>
                                    <div class="acc-dt">
                                        @if(!empty($child->birth_date))
                                            {{ \Carbon\Carbon::parse($child->birth_date)->format('d.m.Y') }}
                                        @else
                                            дата рождения не указана
                                        @endif
                                    </div>
                                </div>

                                <div class="acc-mini">
                                    <div><strong>возраст:</strong> {{ $child->age ?? '—' }}</div>
                                </div>


                                <div class="account-subscription-actions">
                                    <a href="{{ route('account.children.edit', $child->id) }}" class="account-edit-btn">редактировать</a>

                                    @if(Route::has('account.children.subscriptions'))
                                        <a href="{{ route('account.children.subscriptions', $child->id) }}" class="account-edit-btn">абонементы</a>
                                    @endif
                                </div>
                            </div> 
                            </div>

                            
                        @endforeach
                    </div>

                    <div class="account-subscription-actions">
                        <a href="{{ route('account.children.create') }}" class="account-edit-btn">добавить ещё ребёнка</a>
                    </div>
                @endif
            </section>

            {{-- мои тренировки --}}
            <section id="account-trainings" class="account-panel">
                <h2>мои тренировки</h2>

                @if($user->isUser() && $participantTrainingGroups->isEmpty())
                    <div class="muted">вы и ваши дети пока ни на что не записаны.</div>
                @elseif($user->isTrainer() && $sortedTrainings->isEmpty())
                    <div class="muted">
                        у вас пока нет назначенных тренировок.
                    </div>
                @else
                    @if($user->isUser() && $children->isNotEmpty() && $participantTrainingGroups->isNotEmpty())
                        <div class="account-subscription-card" style="margin-bottom: 12px;">
                            <label class="lbl" for="trainingParticipantFilter">тренировки для:</label>
                            <select id="trainingParticipantFilter" class="inp">
                                @foreach($participantTrainingGroups as $group)
                                    <option value="{{ ($group['bookable_type'] ?? 'user') . ':' . (int)($group['bookable_id'] ?? 0) }}">
                                        {{ $group['participant_name'] ?? 'участник' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="account-list">
                        @if($user->isUser())
                            @foreach($participantTrainingGroups as $group)
                                @php
                                    $groupTrainings = collect($group['trainings'] ?? []);
                                    $groupKey = ($group['bookable_type'] ?? 'user') . ':' . (int)($group['bookable_id'] ?? 0);
                                @endphp

                                @if($groupTrainings->isEmpty())
                                    @continue
                                @endif

                                <div class="js-training-participant-group" data-participant-key="{{ $groupKey }}">

                                    @foreach($groupTrainings as $t)
                                        @php
                                            $room = $t->rooms->first() ?? null;
                                            $bookingMeta = $t->booking_meta ?? null;

                                            $bg = $typeColors[$t->type] ?? '#777777';
                                            $typeLabel = $typeNames[$t->type] ?? $t->type;

                                            $dateLabel = $t->date ? \Carbon\Carbon::parse($t->date)->format('d.m.Y') : '—';
                                            $timeLabel = $t->time ? \Carbon\Carbon::parse($t->time)->format('H:i') : '—';

                                            $price = (int)($t->price ?? ($bookingMeta->price ?? ($t->pivot->price ?? 0)));
                                            $bookingStatus = $bookingMeta->status ?? ($t->pivot->status ?? null);
                                            $bookableType = $bookingMeta->bookable_type ?? ($group['bookable_type'] ?? 'user');
                                            $bookableId = (int)($bookingMeta->bookable_id ?? ($group['bookable_id'] ?? $user->id));

                                            $isCancelledTraining = !empty($t->is_cancelled);
                                            $isCancelledBooking = ($bookingStatus && $bookingStatus !== 'active');
                                        @endphp

                                        <div class="account-training account-training--colored" style="background-color: {{ $bg }};">
                                            <div class="acc-top">
                                                <div class="acc-type">{{ $typeLabel }}</div>
                                                <div class="acc-dt">{{ $dateLabel }} {{ $timeLabel }}</div>
                                            </div>

                                            <div class="acc-mini">
                                                <div><strong>длительность:</strong> {{ $t->duration }}</div>
                                                <div><strong>цена:</strong> {{ $price }} ₽ / чел</div>
                                            </div>

                                            <div class="acc-links">
                                                <div>
                                                    <strong>тренер:</strong>
                                                    @if($t->trainer)
                                                        <a class="acc-link" href="{{ route('trainers.show', $t->trainer->id) }}">{{ $t->trainer->full_name }}</a>
                                                    @else
                                                        <span class="acc-muted">не назначен</span>
                                                    @endif
                                                </div>

                                                <div>
                                                    <strong>место:</strong>
                                                    @if($room)
                                                        <a class="acc-link" href="{{ route('rooms.view', $room->id) }}">{{ $room->name }}</a>
                                                    @else
                                                        <span class="acc-muted">не указано</span>
                                                    @endif
                                                </div>
                                            </div>

                                            @if($isCancelledTraining)
                                                <div class="muted">тренировка отменена администратором.</div>
                                            @elseif($isCancelledBooking)
                                                <div class="muted">запись отменена.</div>
                                            @endif

                                            @if(!$isCancelledTraining && !$isCancelledBooking)
                                                <form method="POST" action="{{ route('trainings.cancel', $t->id) }}" data-confirm="отменить запись на тренировку?">
                                                    @csrf
                                                    <input type="hidden" name="bookable_type" value="{{ $bookableType }}">
                                                    <input type="hidden" name="bookable_id" value="{{ $bookableId }}">
                                                    <button class="btn-card btn-card--danger" type="submit">отменить запись</button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                            <div id="trainingParticipantEmpty" class="muted" style="display:none;">
                                у выбранного участника пока нет активных записей.
                            </div>
                        @else
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
                                    <div><strong>длительность:</strong> {{ $t->duration }}</div>
                                    <div><strong>цена:</strong> {{ $price }} ₽ / чел</div>
                                </div>

                                <div class="acc-links">
                                    <div>
                                        <strong>тренер:</strong>
                                        @if($t->trainer)
                                            <a class="acc-link" href="{{ route('trainers.show', $t->trainer->id) }}">{{ $t->trainer->full_name }}</a>
                                        @else
                                            <span class="acc-muted">не назначен</span>
                                        @endif
                                    </div>

                                    <div>
                                        <strong>место:</strong>
                                        @if($room)
                                            <a class="acc-link" href="{{ route('rooms.view', $room->id) }}">{{ $room->name }}</a>
                                        @else
                                            <span class="acc-muted">не указано</span>
                                        @endif
                                    </div>
                                </div>

                                @if($isCancelledTraining)
                                    <div class="muted">тренировка отменена администратором.</div>
                                @elseif($isCancelledBooking)
                                    <div class="muted">запись отменена.</div>
                                @endif

                                @if(!$isCancelledTraining)
                                    @if($user->isUser() && !$isCancelledBooking)
                                        <form method="POST" action="{{ route('trainings.cancel', $t->id) }}" data-confirm="отменить запись на тренировку?">
                                            @csrf
                                            <button class="btn-card btn-card--danger" type="submit">отменить запись</button>
                                        </form>
                                    @endif

                                    @if($user->isTrainer() && (int)$t->trainer_id === (int)$user->id)
                                        @if(!empty($t->has_pending_cancel))
                                            <div class="muted">заявка на отмену отправлена</div>
                                        @else
                                            <form method="POST"
                                                  action="{{ route('trainings.request_cancel', $t->id) }}"
                                                  class="trainer-cancel-row">
                                                @csrf
                                                <input class="input-mini" type="text" name="reason" placeholder="причина">
                                                <button class="btn-card btn-card--warning" type="submit">запросить отмену</button>
                                            </form>
                                        @endif
                                    @endif
                                @endif
                            </div>
                        @endforeach
                        @endif
                    </div>
                @endif
            </section>

            {{-- аренда --}}
            @if($user->isUser())
                <section id="account-courts" class="account-panel">
                    <h2>моя аренда кортов</h2>

                    @if(empty($courtBookings) || $courtBookings->isEmpty())
                        <div class="muted">у вас пока нет арендованных кортов.</div>
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
                                        <div class="acc-type">аренда корта</div>
                                        <div class="acc-dt">{{ $dateLabel }} {{ $timeLabel }}</div>
                                    </div>

                                    <div class="acc-mini">
                                        <div><strong>корт:</strong> {{ optional($booking->room)->name ?? '—' }}</div>
                                        <div><strong>часов:</strong> {{ $booking->hours_count ?? 1 }}</div>
                                        <div><strong>человек:</strong> {{ $booking->persons ?? 1 }}</div>
                                        <div><strong>стоимость:</strong> {{ (int)($booking->total_price ?? $booking->price) }} ₽</div>
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

                                    <form method="POST" action="{{ route('account.court-bookings.cancel', $groupKey) }}" data-confirm="отменить аренду корта?">
                                        @csrf
                                        <button class="btn-card btn-card--danger" type="submit">отменить аренду</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        </div>

        <aside class="account-sidebar">
            <div class="account-sidebar__title">личный кабинет</div>

            <button type="button" class="account-sidebar__link js-account-tab is-active" data-tab="account-info">
                аккаунт
            </button>

            <button type="button" class="account-sidebar__link js-account-tab" data-tab="account-children">
                мои дети
            </button>

            <button type="button" class="account-sidebar__link js-account-tab" data-tab="account-trainings">
                тренировки
            </button>

            @if($user->isUser())
                <button type="button" class="account-sidebar__link js-account-tab" data-tab="account-courts">
                    аренда
                </button>
            @endif

            <button type="button" class="account-sidebar__link js-account-tab" data-tab="account-subscription">
                абонемент
            </button>
        </aside>
    </div>
</div>

@if($showInstallmentPay)
<script>
window.accountInstallmentSavedCard = @json($savedCardForModal ?? null);
</script>
<div class="modal is-hidden" id="installmentPayModal" aria-hidden="true">
    <div class="modal__overlay" data-inst-pay-close="1"></div>
    <div class="modal__dialog modal__dialog--subscription-pay" role="dialog" aria-modal="true" aria-labelledby="installmentPayTitle">
        <button type="button" class="modal__close" data-inst-pay-close="1">×</button>
        <div class="modal__header">
            <div class="modal__title" id="installmentPayTitle">внести платёж</div>
            <div class="modal__subtitle muted">оплата по рассрочке</div>
        </div>
        <div class="modal__body">
            <form method="POST" action="{{ route('subscriptions.installment-payment.init') }}" id="installmentPayForm" novalidate>
                @csrf
                <input type="hidden" name="subscription_id" id="installmentPaySubscriptionId" value="">
                <div class="subscription-pay-total">
                    <span class="subscription-pay-total__label">остаток к оплате по договору</span>
                    <span class="subscription-pay-total__value" id="installmentPayMaxLabel">—</span>
                </div>
                <label class="form-label" for="installment_amount" style="margin-top:14px;">сумма платежа (₽)</label>
                <input type="number"
                       class="form-input"
                       name="amount"
                       id="installment_amount"
                       min="1"
                       step="1"
                       required>
                <hr class="subscription-pay-sep">
                <div class="subscription-pay-grid">
                    <div class="subscription-pay-grid--full">
                        <label class="form-label" for="inst_card_number">номер карты</label>
                        <input type="text" class="form-input" name="card_number" id="inst_card_number" inputmode="numeric" autocomplete="cc-number" placeholder="0000 0000 0000 0000" required>
                    </div>
                    <div>
                        <label class="form-label" for="inst_card_expiry">срок действия</label>
                        <input type="text" class="form-input" name="card_expiry" id="inst_card_expiry" inputmode="numeric" autocomplete="cc-exp" placeholder="ММ/ГГ" maxlength="5" required>
                    </div>
                    <div>
                        <label class="form-label" for="inst_card_cvv">CVV</label>
                        <input type="password" class="form-input" name="card_cvv" id="inst_card_cvv" inputmode="numeric" maxlength="4" placeholder="•••" required>
                    </div>
                    <div class="subscription-pay-grid--full">
                        <label class="form-checkbox" style="margin-top:4px;">
                            <input type="checkbox" class="checkbox-input" name="remember_card" value="1">
                            <span class="checkbox-label">запомнить карту</span>
                        </label>
                    </div>
                </div>
                <button type="submit" class="form-button" style="margin-top:18px;width:100%;">отправить код</button>
            </form>
        </div>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.js-account-tab');
    const panels = document.querySelectorAll('.account-panel');
    const participantFilter = document.getElementById('trainingParticipantFilter');
    const participantGroups = document.querySelectorAll('.js-training-participant-group');
    const participantEmpty = document.getElementById('trainingParticipantEmpty');

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

    function applyTrainingParticipantFilter() {
        if (!participantFilter || !participantGroups.length) return;

        const selected = participantFilter.value || 'all';
        let visibleCount = 0;

        participantGroups.forEach(function (group) {
            const key = group.getAttribute('data-participant-key') || '';
            const visible = selected === 'all' || key === selected;
            group.style.display = visible ? '' : 'none';
            if (visible) visibleCount++;
        });

        if (participantEmpty) {
            participantEmpty.style.display = visibleCount > 0 ? 'none' : '';
        }
    }

    if (participantFilter) {
        participantFilter.addEventListener('change', applyTrainingParticipantFilter);
        applyTrainingParticipantFilter();
    }

    (function () {
        var modal = document.getElementById('installmentPayModal');
        var form = document.getElementById('installmentPayForm');
        if (!modal || !form) return;

        var subIdInput = document.getElementById('installmentPaySubscriptionId');
        var amtInput = document.getElementById('installment_amount');
        var maxLabel = document.getElementById('installmentPayMaxLabel');
        var cardNumber = document.getElementById('inst_card_number');
        var cardExpiry = document.getElementById('inst_card_expiry');

        function formatMoney(n) {
            return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
        }

        function validateCardExpiryClient(val) {
            var m = val.match(/^(0[1-9]|1[0-2])\/(\d{2})$/);
            if (!m) return 'неверный формат срока (ММ/ГГ).';
            var month = parseInt(m[1], 10);
            var year = 2000 + parseInt(m[2], 10);
            var now = new Date();
            var cy = now.getFullYear();
            if (year < cy - 1 || year > cy + 25) return 'укажите корректный год на карте.';
            var lastMs = new Date(year, month, 0).setHours(0, 0, 0, 0);
            var todayMs = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
            if (lastMs < todayMs) return 'срок действия карты истёк.';
            return '';
        }

        function openInstModal() {
            modal.classList.remove('is-hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('no-scroll');
        }

        function closeInstModal() {
            modal.classList.add('is-hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('no-scroll');
        }

        modal.querySelectorAll('[data-inst-pay-close]').forEach(function (el) {
            el.addEventListener('click', closeInstModal);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('is-hidden')) closeInstModal();
        });

        document.querySelectorAll('.js-installment-pay-open').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var sid = btn.getAttribute('data-subscription-id') || '';
                var suggested = parseInt(btn.getAttribute('data-suggested'), 10) || 1;
                var max = parseInt(btn.getAttribute('data-max'), 10) || 1;
                form.reset();
                subIdInput.value = sid;
                amtInput.value = suggested;
                amtInput.max = max;
                amtInput.min = 1;
                if (maxLabel) maxLabel.textContent = formatMoney(max) + ' ₽';
                var s = window.accountInstallmentSavedCard;
                if (s && s.number && cardNumber) cardNumber.value = s.number;
                if (s && s.expiry && cardExpiry) cardExpiry.value = s.expiry;
                openInstModal();
                openTab('account-subscription');
            });
        });

        if (cardNumber) {
            cardNumber.addEventListener('input', function () {
                var d = cardNumber.value.replace(/\D/g, '').slice(0, 19);
                var parts = [];
                for (var i = 0; i < d.length; i += 4) parts.push(d.slice(i, i + 4));
                cardNumber.value = parts.join(' ');
            });
        }
        if (cardExpiry) {
            cardExpiry.addEventListener('input', function () {
                var v = cardExpiry.value.replace(/\D/g, '').slice(0, 4);
                cardExpiry.value = v.length >= 2 ? v.slice(0, 2) + '/' + v.slice(2) : v;
                cardExpiry.setCustomValidity('');
            });
        }

        form.addEventListener('submit', function (e) {
            var max = parseInt(amtInput.max, 10) || 999999999;
            var n = parseInt(amtInput.value, 10);
            if (!n || n < 1) {
                e.preventDefault();
                amtInput.setCustomValidity('укажите сумму');
                amtInput.reportValidity();
                amtInput.setCustomValidity('');
                return;
            }
            if (n > max) {
                e.preventDefault();
                amtInput.setCustomValidity('не больше ' + max + ' ₽');
                amtInput.reportValidity();
                amtInput.setCustomValidity('');
                return;
            }
            if (cardExpiry) {
                var err = validateCardExpiryClient((cardExpiry.value || '').trim());
                cardExpiry.setCustomValidity(err || '');
                if (err) {
                    e.preventDefault();
                    cardExpiry.reportValidity();
                    return;
                }
            }
        });
    })();
});
</script>
@endsection