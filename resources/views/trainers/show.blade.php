@extends('layouts.app')

@section('title', 'Тренер')

@section('content')
<div class="container">
    <h1>{{ $trainer->name }}</h1>

    <div class="profile-card">
        @if($trainer->photo)
            <img class="profile-photo" src="{{ asset('storage/'.$trainer->photo) }}" alt="photo">
        @endif

        <div class="profile-info">
            <div><strong>Специализация:</strong> {{ $trainer->specialization_name }}</div>
            @if($trainer->age)
                <div><strong>Возраст:</strong> {{ $trainer->age }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
