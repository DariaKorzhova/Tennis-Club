@extends('layouts.app')

@section('title', 'главная')

@section('content')

{{-- HERO --}}
<section class="home-hero" id="top">
    <div class="home-hero__inner">
        <div class="home-hero__text">
            <p class="home-hero__brand">The Riverside Tennis Club</p>
            <h1 class="home-hero__title">теннисный клуб</h1>

            <p class="home-hero__desc">
                индивидуальные и групповые занятия теннисом.<br>
                премиальные корты, опытные тренеры, уютная атмосфера. наслаждайтесь игрой в теннис на берегу камы.
                у нас вы найдете идеальное сочетание спорта и природы.
            </p>

            <div class="home-hero__actions">
                <a class="btn-cta" href="{{ route('trainings.show') }}">записаться на тренировку</a>
                <a class="btn-ghost" href="#about">о клубе</a>
            </div>
        </div>
    </div>
</section>

{{-- ABOUT --}}
<section class="home-about" id="about">
    <div class="home-about__grid">
        <div class="home-about__img">
            <img src="{{ asset('storage/corts/default.jpg') }}" alt="корт">
            <span class="home-about__tag">корт №2</span>
        </div>

        <div class="home-about__text">
            <h2 class="home-h2">о клубе</h2>

            <p class="home-p">
                the riverside tennis club – это теннисный клуб на берегу камы, существующий с 2014 года.
                мы предлагаем премиальные корты, опытных тренеров и атмосферу, где гармония природы и спорта
                сливаются воедино.
            </p>

            <p class="home-p">
                наша задача – создать идеальное место для тех, кто ценит качество и комфорт:
                для тренировок, любительских матчей и приятного отдыха.
            </p>
        </div>
    </div>
</section>

{{-- STATS --}}
<section class="home-stats">
    <div class="home-stats__bar">
        <div class="stat">
            <div class="stat__n">7</div>
            <div class="stat__t">премиальных кортов</div>
        </div>
        <div class="stat">
            <div class="stat__n">20</div>
            <div class="stat__t">опытных тренеров</div>
        </div>
        <div class="stat">
            <div class="stat__n">100+</div>
            <div class="stat__t">положительных отзывов</div>
        </div>
        <div class="stat">
            <div class="stat__n">8</div>
            <div class="stat__t">лет опыта</div>
        </div>
    </div>
</section>

{{-- COME TRAIN --}}
<section class="home-come" id="services">
    <div class="home-come__grid">
        <div class="home-come__title">
            <h2 class="home-h2 home-h2--big">приходи на<br>тренировку</h2>

            <div class="home-come__ctaCard">
                <div class="ctaCard__title">быстрая запись</div>
                <div class="ctaCard__desc">открой расписание и выбери тренировку на удобный день</div>

                <a class="btn-cta btn-cta--wide" href="{{ route('trainings.show') }}">открыть расписание</a>
            </div>
        </div>

        <div class="home-come__steps">
            <div class="step">
                <div class="step__n">1</div>
                <div class="step__t">выбери тип тренировки</div>
            </div>
            <div class="step">
                <div class="step__n">2</div>
                <div class="step__t">посмотри расписание на неделю</div>
            </div>
            <div class="step">
                <div class="step__n">3</div>
                <div class="step__t">запишись на удобное время</div>
            </div>
            <div class="step">
                <div class="step__n">4</div>
                <div class="step__t">приходи и наслаждайся игрой</div>
            </div>
        </div>
    </div>
</section>

{{-- OUR SERVICES --}}
<section class="home-services" id="prices">
    <h2 class="home-h2">наши услуги</h2>

    <div class="service-grid">
        <div class="service-card">
            <div class="service-card__title">индивидуальная тренировка</div>
            <div class="service-card__desc">персональная работа над техникой, подачей и тактикой.</div>
            <div class="service-card__meta">длительность: 1 час</div>
            <div class="service-card__meta">стоимость: от 3000 ₽</div>
            <a class="service-card__btn" href="{{ route('trainings.show', ['type' => 'individual']) }}">смотреть</a>
        </div>

        <div class="service-card">
            <div class="service-card__title">детский теннис</div>
            <div class="service-card__desc">тренировки для детей, развитие координации и базовой техники.</div>
            <div class="service-card__meta">длительность: 1 час</div>
            <div class="service-card__meta">стоимость: от 2000 ₽</div>
            <a class="service-card__btn" href="{{ route('trainings.show', ['type' => 'kids']) }}">смотреть</a>
        </div>

        <div class="service-card">
            <div class="service-card__title">групповая тренировка</div>
            <div class="service-card__desc">тренировки в мини-группах: динамика, игра, спарринги.</div>
            <div class="service-card__meta">длительность: 1.5–2 часа</div>
            <div class="service-card__meta">стоимость: от 1500 ₽</div>
            <a class="service-card__btn" href="{{ route('trainings.show', ['type' => 'group']) }}">смотреть</a>
        </div>

        <div class="service-card">
            <div class="service-card__title">аренда кортов</div>
            <div class="service-card__desc">почасовая аренда кортов для игры и самостоятельных тренировок.</div>
            <div class="service-card__meta">длительность: 1 час</div>
            <div class="service-card__meta">стоимость: от 2500 ₽</div>
            <a class="service-card__btn" href="{{ route('rooms.show') }}">корты</a>
        </div>
    </div>
</section>

{{-- PRICES --}}
<section class="home-prices">
    <h2 class="home-h2">цены</h2>

    <div class="price-grid">
        <div class="price-card">
            <div class="price-card__name">индивидуальные тренировки</div>
            <div class="price-card__line">1 час</div>
            <div class="price-card__price">3000 ₽</div>
        </div>
        <div class="price-card">
            <div class="price-card__name">групповые тренировки</div>
            <div class="price-card__line">4 тренировки</div>
            <div class="price-card__price">10 000 ₽</div>
        </div>
        <div class="price-card">
            <div class="price-card__name">детский теннис</div>
            <div class="price-card__line">8 тренировок</div>
            <div class="price-card__price">18 000 ₽</div>
        </div>
        <div class="price-card">
            <div class="price-card__name">аренда теннисных кортов</div>
            <div class="price-card__line">16 тренировок</div>
            <div class="price-card__price">30 000 ₽</div>
        </div>
    </div>
</section>

{{-- TRAINERS --}}
<section class="home-trainers" id="trainers">
    <h2 class="home-h2">наши тренеры</h2>

    <div class="trainer-row">
        <a class="trainer-card" href="{{ url('/trainers/1') }}">
            <div class="trainer-card__img" style="background-image:url('{{ asset('storage/trainers/maria.jpg') }}')"></div>
            <div class="trainer-card__name">мария иванова</div>
            <div class="trainer-card__role">тренер по теннису</div>
        </a>

        <a class="trainer-card" href="{{ url('/trainers/2') }}">
            <div class="trainer-card__img" style="background-image:url('{{ asset('storage/trainers/ivan.jpg') }}')"></div>
            <div class="trainer-card__name">игорь петров</div>
            <div class="trainer-card__role">тренер по теннису</div>
        </a>

        <a class="trainer-card" href="{{ url('/trainers/3') }}">
            <div class="trainer-card__img" style="background-image:url('{{ asset('storage/trainers/anna.jpg') }}')"></div>
            <div class="trainer-card__name">анна смирнова</div>
            <div class="trainer-card__role">фитнес-тренер</div>
        </a>

        <a class="trainer-card" href="{{ url('/trainers/4') }}">
            <div class="trainer-card__img" style="background-image:url('{{ asset('storage/trainers/kiril.jpg') }}')"></div>
            <div class="trainer-card__name">кирилл семёнов</div>
            <div class="trainer-card__role">тренер по теннису</div>
        </a>
    </div>
</section>

{{-- REVIEWS --}}
<section class="home-reviews" id="reviews">
    <h2 class="home-h2">отзывы</h2>

    <div class="review-grid">
        <div class="review">
            <div class="review__t">“отличные корты и атмосфера. тренеры реально помогают прогрессировать.”</div>
            <div class="review__a">— алексей</div>
        </div>
        <div class="review">
            <div class="review__t">“детские тренировки очень понравились, ребёнок ходит с удовольствием.”</div>
            <div class="review__a">— екатерина</div>
        </div>
        <div class="review">
            <div class="review__t">“удобное расписание, легко записаться и отменить. рекомендую.”</div>
            <div class="review__a">— дмитрий</div>
        </div>
    </div>
</section>

{{-- CONTACTS --}}
<section class="home-contacts" id="contacts">
    <h2 class="home-h2">контакты</h2>

    <div class="contacts-bar">
        <div class="contacts-item">
            <div class="contacts-k">телефон</div>
            <div class="contacts-v">+7 (999) 999-99-99</div>
        </div>
        <div class="contacts-item">
            <div class="contacts-k">адрес</div>
            <div class="contacts-v">ул. московская, 1</div>
        </div>
        <div class="contacts-item">
            <div class="contacts-k">почта</div>
            <div class="contacts-v">theriverside@mail.ru</div>
        </div>
        <div class="contacts-item">
            <div class="contacts-k">соц. сети</div>
            <div class="contacts-v">VK / Telegram</div>
        </div>
    </div>

    <div class="map-frame">
        <div class="map-placeholder">
            карта
        </div>
    </div>
</section>

@endsection
