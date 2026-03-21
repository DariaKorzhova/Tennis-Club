@extends('layouts.app')

@section('title', 'Админ панель - Пользователи')

@section('content')
<div class="admin-container">
    <div class="admin-header">
        <h2>Управление пользователями</h2>
        <p class="admin-subtitle">Всего пользователей: {{ $users->count() }}</p>
    </div>

    
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Возраст</th>
                    <th>Роль</th>
                    <th>Специализация</th>
                    <th>Дата регистрации</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->age }}</td>
                    <td>
                        <span class="role-badge role-{{ $user->role }}">
                            {{ $roles[$user->role] ?? $user->role }}
                        </span>
                    </td>
                    <td>
                        @if($user->role === 'trainer' && $user->specialization)
                            <span class="specialization-badge">
                                {{ $specializations[$user->specialization] ?? $user->specialization }}
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>{{ $user->created_at->format('d.m.Y H:i') }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.users.update-role', $user) }}" class="role-form">
                            @csrf
                            <div class="role-selector">
                                <select name="role" class="role-select" onchange="this.form.submit()">
                                    @foreach($roles as $key => $label)
                                        <option value="{{ $key }}" {{ $user->role == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                
                                @if($user->role === 'trainer')
                                <select name="specialization" class="specialization-select" onchange="this.form.submit()">
                                    @foreach($specializations as $key => $label)
                                        <option value="{{ $key }}" {{ $user->specialization == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @endif
                            </div>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    
    <div class="admin-stats">
        <div class="stat-card">
            <h3>Статистика по ролям</h3>
            <ul class="stats-list">
                <li>Пользователей: {{ $users->where('role', 'user')->count() }}</li>
                <li>Администраторов: {{ $users->where('role', 'admin')->count() }}</li>
                <li>Тренеров: {{ $users->where('role', 'trainer')->count() }}</li>
            </ul>
        </div>
        
        <div class="stat-card">
            <h3>Статистика по специализациям</h3>
            <ul class="stats-list">
                <li>Тренеры по теннису: {{ $users->where('specialization', 'tennis_trainer')->count() }}</li>
                <li>Тренеры по фитнесу: {{ $users->where('specialization', 'fitness_trainer')->count() }}</li>
                <li>Тренеры по йоге: {{ $users->where('specialization', 'yoga_trainer')->count() }}</li>
                <li>Массажисты: {{ $users->where('specialization', 'masseur')->count() }}</li>
            </ul>
        </div>
    </div>
</div>


<script>
    // Автоматическая отправка формы при изменении специализации для тренеров
    document.addEventListener('DOMContentLoaded', function() {
        const specializationSelects = document.querySelectorAll('.specialization-select');
        
        specializationSelects.forEach(select => {
            select.addEventListener('change', function() {
                const form = this.closest('.role-form');
                if (form) {
                    form.submit();
                }
            });
        });
    });
</script>
@endsection