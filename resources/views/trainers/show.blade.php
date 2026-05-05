@extends('layouts.app')

@section('title', 'тренер')

@section('content')
<div class="container">
    <h1>{{ $trainer->name }}</h1>

    <div class="profile-card">
        @if($trainer->photo)
            <img class="profile-photo" src="{{ asset('storage/'.$trainer->photo) }}" alt="photo">
        @endif

        <div class="profile-info">
            <div><strong>специализация:</strong> {{ $trainer->specialization_name }}</div>
            @if($trainer->age)
                <div><strong>возраст:</strong> {{ $trainer->age }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
