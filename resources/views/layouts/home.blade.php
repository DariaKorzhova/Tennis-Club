@extends('layouts.app')

@section('title', 'Главная')

@section('content')

{{-- HERO --}}
<section class="home-hero" id="top">
    <div class="home-hero__inner">
        <div class="home-hero__text">
            <p class="home-hero__brand">The Riverside Tennis Club</p>
            <h1 class="home-hero__title">ТЕННИСНЫЙ КЛУБ</h1>

            <p class="home-hero__desc">
                Индивидуальные и групповые занятия теннисом.<br>
                Премиальные корты, опытные тренеры, уютная атмосфера. Наслаждайтесь игрой в теннис на берегу Камы.
                У нас вы найдете идеальное сочетание спорта и природы.
            </p>

            <div class="home-hero__actions">
                <a class="btn-cta" href="{{ route('trainings.show') }}">Записаться на тренировку</a>
                <a class="btn-ghost" href="#about">О клубе</a>
            </div>
        </div>
    </div>
</section>

{{-- ABOUT --}}
<section class="home-about" id="about">
    <div class="home-about__grid">
        <div class="home-about__img">
            <img src="{{ asset('storage/corts/default.jpg') }}" alt="Корт">
            <span class="home-about__tag">Корт №2</span>
        </div>

        <div class="home-about__text">
            <h2 class="home-h2">О КЛУБЕ</h2>

            <p class="home-p">
                The Riverside Tennis Club – это теннисный клуб на берегу Камы, существующий с 2014 года.
                Мы предлагаем премиальные корты, опытных тренеров и атмосферу, где гармония природы и спорта
                сливаются воедино.
            </p>

            <p class="home-p">
                Наша задача – создать идеальное место для тех, кто ценит качество и комфорт:
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
            <h2 class="home-h2 home-h2--big">ПРИХОДИ НА<br>ТРЕНИРОВКУ</h2>

            <div class="home-come__ctaCard">
                <div class="ctaCard__title">Быстрая запись</div>
                <div class="ctaCard__desc">Открой расписание и выбери тренировку на удобный день</div>

                <a class="btn-cta btn-cta--wide" href="{{ route('trainings.show') }}">Открыть расписание</a>
            </div>
        </div>

        <div class="home-come__steps">
            <div class="step">
                <div class="step__n">1</div>
                <div class="step__t">Выбери тип тренировки</div>
            </div>
            <div class="step">
                <div class="step__n">2</div>
                <div class="step__t">Посмотри расписание на неделю</div>
            </div>
            <div class="step">
                <div class="step__n">3</div>
                <div class="step__t">Запишись на удобное время</div>
            </div>
            <div class="step">
                <div class="step__n">4</div>
                <div class="step__t">Приходи и наслаждайся игрой</div>
            </div>
        </div>
    </div>
</section>

{{-- OUR SERVICES --}}
<section class="home-services" id="prices">
    <h2 class="home-h2">НАШИ УСЛУГИ</h2>

    <div class="service-grid">
        <div class="service-card">
            <div class="service-card__title">Индивидуальная тренировка</div>
            <div class="service-card__desc">Персональная работа над техникой, подачей и тактикой.</div>
            <div class="service-card__meta">Длительность: 1 час</div>
            <div class="service-card__meta">Стоимость: от 3000 ₽</div>
            <a class="service-card__btn" href="{{ route('trainings.show', ['type' => 'individual']) }}">Смотреть</a>
        </div>

        <div class="service-card">
            <div class="service-card__title">Детский теннис</div>
            <div class="service-card__desc">Тренировки для детей, развитие координации и базовой техники.</div>
            <div class="service-card__meta">Длительность: 1 час</div>
            <div class="service-card__meta">Стоимость: от 2000 ₽</div>
            <a class="service-card__btn" href="{{ route('trainings.show', ['type' => 'kids']) }}">Смотреть</a>
        </div>

        <div class="service-card">
            <div class="service-card__title">Групповая тренировка</div>
            <div class="service-card__desc">Тренировки в мини-группах: динамика, игра, спарринги.</div>
            <div class="service-card__meta">Длительность: 1.5–2 часа</div>
            <div class="service-card__meta">Стоимость: от 1500 ₽</div>
            <a class="service-card__btn" href="{{ route('trainings.show', ['type' => 'group']) }}">Смотреть</a>
        </div>

        <div class="service-card">
            <div class="service-card__title">Аренда кортов</div>
            <div class="service-card__desc">Почасовая аренда кортов для игры и самостоятельных тренировок.</div>
            <div class="service-card__meta">Длительность: 1 час</div>
            <div class="service-card__meta">Стоимость: от 2500 ₽</div>
            <a class="service-card__btn" href="{{ route('rooms.show') }}">Корты</a>
        </div>
    </div>
</section>

{{-- PRICES --}}
<section class="home-prices">
    <h2 class="home-h2">ЦЕНЫ</h2>

    <div class="price-grid">
        <div class="price-card">
            <div class="price-card__name">Индивидуальные тренировки</div>
            <div class="price-card__line">1 час</div>
            <div class="price-card__price">3000 ₽</div>
        </div>
        <div class="price-card">
            <div class="price-card__name">Групповые тренировки</div>
            <div class="price-card__line">4 тренировки</div>
            <div class="price-card__price">10 000 ₽</div>
        </div>
        <div class="price-card">
            <div class="price-card__name">Детский теннис</div>
            <div class="price-card__line">8 тренировок</div>
            <div class="price-card__price">18 000 ₽</div>
        </div>
        <div class="price-card">
            <div class="price-card__name">Аренда теннисных кортов</div>
            <div class="price-card__line">16 тренировок</div>
            <div class="price-card__price">30 000 ₽</div>
        </div>
    </div>
</section>

{{-- TRAINERS --}}
<section class="home-trainers" id="trainers">
    <h2 class="home-h2">НАШИ ТРЕНЕРЫ</h2>

    <div class="trainer-row">
        <a class="trainer-card" href="{{ url('/trainers/1') }}">
            <div class="trainer-card__img" style="background-image:url('{{ asset('storage/trainers/maria.jpg') }}')"></div>
            <div class="trainer-card__name">Мария Иванова</div>
            <div class="trainer-card__role">Тренер по теннису</div>
        </a>

        <a class="trainer-card" href="{{ url('/trainers/2') }}">
            <div class="trainer-card__img" style="background-image:url('{{ asset('storage/trainers/ivan.jpg') }}')"></div>
            <div class="trainer-card__name">Игорь Петров</div>
            <div class="trainer-card__role">Тренер по теннису</div>
        </a>

        <a class="trainer-card" href="{{ url('/trainers/3') }}">
            <div class="trainer-card__img" style="background-image:url('{{ asset('storage/trainers/anna.jpg') }}')"></div>
            <div class="trainer-card__name">Анна Смирнова</div>
            <div class="trainer-card__role">Фитнес-тренер</div>
        </a>

        <a class="trainer-card" href="{{ url('/trainers/4') }}">
            <div class="trainer-card__img" style="background-image:url('{{ asset('storage/trainers/kiril.jpg') }}')"></div>
            <div class="trainer-card__name">Кирилл Семёнов</div>
            <div class="trainer-card__role">Тренер по теннису</div>
        </a>
    </div>
</section>

{{-- REVIEWS --}}
<section class="home-reviews" id="reviews">
    <h2 class="home-h2">ОТЗЫВЫ</h2>

    <div class="review-grid">
        <div class="review">
            <div class="review__t">“Отличные корты и атмосфера. Тренеры реально помогают прогрессировать.”</div>
            <div class="review__a">— Алексей</div>
        </div>
        <div class="review">
            <div class="review__t">“Детские тренировки очень понравились, ребёнок ходит с удовольствием.”</div>
            <div class="review__a">— Екатерина</div>
        </div>
        <div class="review">
            <div class="review__t">“Удобное расписание, легко записаться и отменить. Рекомендую.”</div>
            <div class="review__a">— Дмитрий</div>
        </div>
    </div>
</section>

{{-- CONTACTS --}}
<section class="home-contacts" id="contacts">
    <h2 class="home-h2">КОНТАКТЫ</h2>

    <div class="contacts-bar">
        <div class="contacts-item">
            <div class="contacts-k">Телефон</div>
            <div class="contacts-v">+7 (999) 999-99-99</div>
        </div>
        <div class="contacts-item">
            <div class="contacts-k">Адрес</div>
            <div class="contacts-v">ул. Московская, 1</div>
        </div>
        <div class="contacts-item">
            <div class="contacts-k">Почта</div>
            <div class="contacts-v">theriverside@mail.ru</div>
        </div>
        <div class="contacts-item">
            <div class="contacts-k">Соц. сети</div>
            <div class="contacts-v">VK / Telegram</div>
        </div>
    </div>

    <div class="map-frame">
        <div class="map-placeholder">
            Карта
        </div>
    </div>
</section>

@endsection
