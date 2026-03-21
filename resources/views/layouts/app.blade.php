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
    <header>
        <div class="container">
          <nav>
            <div class="menuList">
                <a href="{{route('home')}}">главная</a>
                <a href="{{route('rooms.show')}}">корты</a>
                <a href="{{route('trainings.show')}}">тренировки</a>

            @auth
                @if(Auth::user()->role === 'admin')
                    <a href="{{ route('admin.users') }}">админ-панель</a>
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

    <footer>
        {{-- Футер можно оставить пустым или добавить контент позже --}}
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alertsContainer = document.querySelector('.alerts-container');
            const alerts = document.querySelectorAll('.alert');

            function closeAlert(alert) {
                alert.classList.add('hiding');
                setTimeout(() => {
                    alert.remove();
                    if (alertsContainer.children.length === 0) {
                        alertsContainer.style.display = 'none';
                    }
                }, 300);
            }

            document.querySelectorAll('.alert-close').forEach(button => {
                button.addEventListener('click', function() {
                    const alert = this.closest('.alert');
                    closeAlert(alert);
                });
            });

            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.parentElement) {
                        closeAlert(alert);
                    }
                }, 5000);
            });

            alerts.forEach(alert => {
                alert.addEventListener('click', function(e) {
                    if (!e.target.classList.contains('alert-close')) {
                        closeAlert(this);
                    }
                });
            });
        });
    </script>

</body>
</html>
