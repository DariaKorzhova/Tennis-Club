@extends('layouts.app')

@section('title', 'Помещение')

@section('content')
<div class="container">
    <h1>{{ $room->name }}</h1>

    <div class="profile-card">
        @if($room->photo)
            <img class="profile-photo" src="{{ asset('storage/'.$room->photo) }}" alt="photo">
        @endif

        <div class="profile-info">
            <div><strong>Тип:</strong> {{ \App\Models\Room::getRoomTypes()[$room->type] ?? $room->type }}</div>
            @if($room->season)
                <div><strong>Сезон:</strong> {{ $room->season }}</div>
            @endif
            @if($room->description)
                <div class="mt"><strong>Описание:</strong> {{ $room->description }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
