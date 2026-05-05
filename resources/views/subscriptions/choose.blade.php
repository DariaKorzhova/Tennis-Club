@extends('layouts.app')

@section('title', 'выбор абонемента')

@section('content')
<div class="container">
    <div class="subscription-page">
        <h1>выберите абонемент</h1>

        @if(session('error'))
            <div class="form-error" style="margin-top:12px;">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="form-error" style="margin-top:12px;">
                @foreach ($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
        @endif

        <div class="subscription-grid">
            @foreach($plans as $plan)
                @php $dm = max(1, (int) $plan->duration_months); @endphp
                <div class="subscription-card">
                    <div class="subscription-card__title">{{ $plan->name }}</div>
                    <div class="subscription-card__desc">{{ $plan->description }}</div>

                    <div class="subscription-card__price">
                        @if($plan->monthly_price > 0)
                            от {{ $plan->monthly_price }} ₽ / мес
                        @else
                            {{ $plan->full_price }} ₽
                        @endif
                    </div>

                    <ul class="subscription-card__list">
                        <li>{{ $plan->duration_months }} месяц</li>
                        @if($plan->visit_limit)
                            <li>{{ $plan->visit_limit }} посещений</li>
                        @else
                            <li>свободное посещение</li>
                        @endif
                        <li>{{ $plan->freeze_days_per_year }} дней заморозки</li>
                    </ul>

                    <button type="button"
                            class="form-button js-subscription-pay-open"
                            data-plan-id="{{ $plan->id }}"
                            data-plan-name="{{ $plan->name }}"
                            data-full-price="{{ (int) $plan->full_price }}"
                            data-duration-months="{{ $dm }}"
                            data-allows-installment="{{ $plan->allows_installment ? '1' : '0' }}"
                            data-auto-renew-available="{{ $plan->auto_renew_available ? '1' : '0' }}">
                        оформить
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="modal is-hidden" id="subscriptionPayModal" aria-hidden="true">
    <div class="modal__overlay" data-sub-pay-close="1"></div>

    <div class="modal__dialog modal__dialog--subscription-pay" role="dialog" aria-modal="true" aria-labelledby="subscriptionPayTitle">
        <button type="button" class="modal__close" data-sub-pay-close="1">×</button>

        <div class="modal__header">
            <div class="modal__title" id="subscriptionPayTitle">оплата абонемента</div>
            <div class="modal__subtitle muted" id="subscriptionPaySubtitle"></div>
        </div>

        <div class="modal__body">
            <form method="POST" action="{{ route('subscriptions.payment.init') }}" id="subscriptionPayForm" novalidate>
                @csrf
                <input type="hidden" name="plan_id" id="subscriptionPayPlanId" value="{{ old('plan_id') }}">
                <input type="hidden" name="payment_mode" value="one_time" id="subscriptionPaymentModeFixed" class="is-hidden" disabled>

                <div class="subscription-pay-billing js-pay-billing-wrap" id="subscriptionPayBilling">
                    <span class="form-label">способ оплаты</span>
                    <div class="subscription-pay-radio-row">
                        <label class="form-radio">
                            <input type="radio" name="payment_mode" value="one_time" class="js-pay-mode" @checked(old('payment_mode', 'one_time') === 'one_time')>
                            <span>сразу</span>
                        </label>
                        <label class="form-radio js-installment-label">
                            <input type="radio" name="payment_mode" value="installment" class="js-pay-mode" @checked(old('payment_mode') === 'installment')>
                            <span>рассрочка</span>
                        </label>
                    </div>
                </div>

                <div class="subscription-pay-total">
                    <span class="subscription-pay-total__label">к оплате</span>
                    <span class="subscription-pay-total__value" id="subscriptionPayAmount">—</span>
                </div>

                <div class="js-auto-renew-wrap is-hidden" style="margin-top:14px;">
                    <label class="form-checkbox">
                        <input type="checkbox" class="checkbox-input" name="auto_renew" value="1" @checked(old('auto_renew'))>
                        <span class="checkbox-label">автопродление</span>
                    </label>
                </div>

                <hr class="subscription-pay-sep">

                <div class="subscription-pay-grid">
                    <div class="subscription-pay-grid--full">
                        <label class="form-label" for="card_number">номер карты</label>
                        <input type="text"
                               class="form-input @error('card_number') is-invalid @enderror"
                               name="card_number"
                               id="card_number"
                               inputmode="numeric"
                               autocomplete="cc-number"
                               value="{{ old('card_number') }}"
                               placeholder="0000 0000 0000 0000"
                               required>
                        @error('card_number')
                            <div class="form-error" style="margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label" for="card_expiry">срок действия</label>
                        <input type="text"
                               class="form-input @error('card_expiry') is-invalid @enderror"
                               name="card_expiry"
                               id="card_expiry"
                               inputmode="numeric"
                               autocomplete="cc-exp"
                               value="{{ old('card_expiry') }}"
                               placeholder="ММ/ГГ"
                               maxlength="5"
                               required>
                        @error('card_expiry')
                            <div class="form-error" style="margin-top:6px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label" for="card_cvv">CVV</label>
                        <input type="password"
                               class="form-input"
                               name="card_cvv"
                               id="card_cvv"
                               inputmode="numeric"
                               autocomplete="cc-csc"
                               maxlength="4"
                               value=""
                               placeholder="•••"
                               required>
                    </div>
                    <div class="subscription-pay-grid--full">
                        <label class="form-checkbox" style="margin-top:4px;">
                            <input type="checkbox" class="checkbox-input" name="remember_card" value="1" @checked(old('remember_card'))>
                            <span class="checkbox-label">запомнить карту</span>
                        </label>
                    </div>
                </div>

                <button type="submit" class="form-button" style="margin-top:18px; width:100%;">
                    отправить код
                </button>
            </form>
        </div>
    </div>
</div>

<script>
window.subscriptionSavedCard = @json($savedCardForModal ?? null);
</script>
<script>
(function () {
    var modal = document.getElementById('subscriptionPayModal');
    var form = document.getElementById('subscriptionPayForm');
    if (!modal || !form) return;

    var planIdInput = document.getElementById('subscriptionPayPlanId');
    var subtitle = document.getElementById('subscriptionPaySubtitle');
    var payAmountEl = document.getElementById('subscriptionPayAmount');
    var billingWrap = document.getElementById('subscriptionPayBilling');
    var paymentModeFixed = document.getElementById('subscriptionPaymentModeFixed');
    var payModeRadios = modal.querySelectorAll('input.js-pay-mode[name="payment_mode"]');
    var autoRenewWrap = modal.querySelector('.js-auto-renew-wrap');
    var cardNumber = document.getElementById('card_number');
    var cardExpiry = document.getElementById('card_expiry');

    var fullPriceCap = 0;
    var durationMonthsForPlan = 1;

    function formatMoney(n) {
        return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    function validateCardExpiryClient(val) {
        var m = val.match(/^(0[1-9]|1[0-2])\/(\d{2})$/);
        if (!m) {
            return 'неверный формат срока. используйте ММ/ГГ (например, 08/27).';
        }
        var month = parseInt(m[1], 10);
        var year = 2000 + parseInt(m[2], 10);
        var now = new Date();
        var cy = now.getFullYear();
        if (year < cy - 1 || year > cy + 25) {
            return 'укажите корректный год на карте.';
        }
        var lastMs = new Date(year, month, 0).setHours(0, 0, 0, 0);
        var todayMs = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
        if (lastMs < todayMs) {
            return 'срок действия карты истёк.';
        }
        return '';
    }

    function openModal() {
        modal.classList.remove('is-hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('no-scroll');
        var dialog = modal.querySelector('.modal__dialog');
        if (dialog) dialog.scrollTop = 0;
    }

    function closeModal() {
        modal.classList.add('is-hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('no-scroll');
    }

    modal.querySelectorAll('[data-sub-pay-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('is-hidden')) {
            closeModal();
        }
    });

    function currentChargeRub() {
        var full = fullPriceCap;
        var modeInst = modal.querySelector('input[name="payment_mode"][value="installment"]');
        var checkedInst = modeInst && modeInst.checked;
        if (checkedInst) {
            var m = Math.max(1, durationMonthsForPlan);
            return Math.floor(full / m) + (full % m);
        }
        return full;
    }

    function updatePayAmountDisplay() {
        if (!payAmountEl) return;
        var n = currentChargeRub();
        payAmountEl.textContent = formatMoney(n) + ' ₽';
    }

    function syncInstallmentVisibility() {
        updatePayAmountDisplay();
    }

    modal.querySelectorAll('.js-pay-mode').forEach(function (radio) {
        radio.addEventListener('change', syncInstallmentVisibility);
    });

    if (cardNumber) {
        cardNumber.addEventListener('input', function () {
            var d = cardNumber.value.replace(/\D/g, '').slice(0, 19);
            var parts = [];
            for (var i = 0; i < d.length; i += 4) {
                parts.push(d.slice(i, i + 4));
            }
            cardNumber.value = parts.join(' ');
        });
    }

    if (cardExpiry) {
        cardExpiry.addEventListener('input', function () {
            var v = cardExpiry.value.replace(/\D/g, '').slice(0, 4);
            if (v.length >= 2) {
                cardExpiry.value = v.slice(0, 2) + '/' + v.slice(2);
            } else {
                cardExpiry.value = v;
            }
            cardExpiry.setCustomValidity('');
        });
    }

    function applySavedCard() {
        var s = window.subscriptionSavedCard;
        if (!s) return;
        if (s.number && cardNumber) {
            cardNumber.value = s.number;
        }
        if (s.expiry && cardExpiry) {
            cardExpiry.value = s.expiry;
        }
    }

    form.addEventListener('submit', function (e) {
        if (!cardExpiry) return;
        var err = validateCardExpiryClient((cardExpiry.value || '').trim());
        cardExpiry.setCustomValidity(err || '');
        if (err) {
            e.preventDefault();
            cardExpiry.reportValidity();
            return;
        }
        var d = (cardNumber && cardNumber.value) ? cardNumber.value.replace(/\D/g, '') : '';
        if (d.length < 13 || d.length > 19) {
            e.preventDefault();
            if (cardNumber) {
                cardNumber.setCustomValidity('укажите корректный номер карты');
                cardNumber.reportValidity();
                cardNumber.setCustomValidity('');
            }
        }
    });

    function applyPlanToModal(btn) {
        var id = btn.getAttribute('data-plan-id') || '';
        var name = btn.getAttribute('data-plan-name') || '';
        fullPriceCap = parseInt(btn.getAttribute('data-full-price'), 10) || 0;
        durationMonthsForPlan = parseInt(btn.getAttribute('data-duration-months'), 10) || 1;
        var allowInst = btn.getAttribute('data-allows-installment') === '1';
        var autoRenewAvail = btn.getAttribute('data-auto-renew-available') === '1';

        form.reset();
        planIdInput.value = id;
        if (subtitle) {
            subtitle.textContent = name;
        }

        if (!allowInst) {
            if (billingWrap) {
                billingWrap.classList.add('is-hidden');
            }
            if (paymentModeFixed) {
                paymentModeFixed.classList.remove('is-hidden');
                paymentModeFixed.disabled = false;
            }
            payModeRadios.forEach(function (r) {
                r.disabled = true;
            });
            var oneTime = modal.querySelector('input[name="payment_mode"][value="one_time"]');
            if (oneTime) {
                oneTime.checked = true;
            }
        } else {
            if (billingWrap) {
                billingWrap.classList.remove('is-hidden');
            }
            if (paymentModeFixed) {
                paymentModeFixed.classList.add('is-hidden');
                paymentModeFixed.disabled = true;
            }
            payModeRadios.forEach(function (r) {
                r.disabled = false;
            });
        }

        if (autoRenewWrap) {
            autoRenewWrap.classList.toggle('is-hidden', !autoRenewAvail);
        }

        syncInstallmentVisibility();
    }

    document.querySelectorAll('.js-subscription-pay-open').forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyPlanToModal(btn);
            applySavedCard();
            openModal();
        });
    });

    @if(old('plan_id'))
    (function () {
        var pid = '{{ old('plan_id') }}';
        var b = document.querySelector('.js-subscription-pay-open[data-plan-id="' + pid + '"]');
        if (b) {
            applyPlanToModal(b);
            openModal();
        }
    })();
    @endif
})();
</script>
@endsection
