<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="{{asset('css/style.css')}}">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@200..900&display=swap" rel="stylesheet">
    <title>TheRiverSide - @yield('title')</title>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <nav class="site-nav">
                <div class="menuList">
                    <a href="{{route('home')}}">главная</a>
                    <a href="{{route('rooms.show')}}">корты</a>
                    <a href="{{route('trainings.show')}}">тренировки</a>
                    <a href="{{ route('court-rent.index') }}">аренда кортов</a>

                    @auth
                        @if(Auth::user()->role === 'admin')
                            <a href="{{ route('admin.panel') }}">админ-панель</a>
                        @endif

                        @if(in_array(Auth::user()->role, ['user','trainer']))
                            <a href="{{ route('account') }}">мой аккаунт</a>
                        @endif

                        @if(in_array(Auth::user()->role, ['admin', 'trainer']))
                            <div class="user-info">
                                <span class="user-role-badge {{ Auth::user()->role }}">
                                    {{ Auth::user()->role_name }}
                                </span>

                                <form method="POST" action="{{ route('logout') }}" class="logout-form">
                                    @csrf
                                    <button type="submit" class="logout-btn">выйти</button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('logout') }}" class="logout-form">
                                @csrf
                                <button type="submit" class="logout-btn">выйти</button>
                            </form>
                        @endif

                    @else
                        <a href="{{ route('login') }}">вход</a>
                        <a href="{{ route('register') }}">регистрация</a>
                    @endauth
                </div>
            </nav>
        </div>
    </header>

    <div class="alerts-container">
        @if(session('error'))
        <div class="alert error">
            {{session('error')}}
            <button class="alert-close">&times;</button>
        </div>
        @endif

        @if(session('success'))
        <div class="alert success">
            {{session('success')}}
            <button class="alert-close">&times;</button>
        </div>
        @endif
    </div>

    <main>
        <div class="container">
            @yield('content')
        </div>
    </main>

    <footer></footer>

    <script>
        document.addeventlistener('domcontentloaded', function() {
            const alertscontainer = document.queryselector('.alerts-container');
            const alerts = document.queryselectorall('.alert');

            function closealert(alert) {
                alert.classlist.add('hiding');
                settimeout(() => {
                    alert.remove();
                    if (alertscontainer && alertscontainer.children.length === 0) {
                        alertscontainer.style.display = 'none';
                    }
                }, 300);
            }

            document.queryselectorall('.alert-close').foreach(button => {
                button.addeventlistener('click', function() {
                    const alert = this.closest('.alert');
                    closealert(alert);
                });
            });

            alerts.foreach(alert => {
                settimeout(() => {
                    if (alert.parentelement) {
                        closealert(alert);
                    }
                }, 5000);
            });

            alerts.foreach(alert => {
                alert.addeventlistener('click', function(e) {
                    if (!e.target.classlist.contains('alert-close')) {
                        closealert(this);
                    }
                });
            });

            document.queryselectorall('form[data-confirm]').foreach(function(form) {
                form.addeventlistener('submit', function(e) {
                    const message = form.getattribute('data-confirm') || 'подтвердить действие?';
                    if (!window.confirm(message)) {
                        e.preventdefault();
                    }
                });
            });
        });
    </script>

</body>
</html>