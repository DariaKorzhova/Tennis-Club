@extends('layouts.app')

@section('title', 'корты')

@section('content')
<div class="container">
  <h1>помещения</h1>

<div class="corts">
    @foreach ($rooms as $room)
    <div class="cort">
        <div class="roomName">
            <h3>{{ $room->name }}</h3>

            <div>
            @if ($room->type == 'tennis_court')

            @if($room->season == 'open')
                <span>открытый</span>
            @else
                <span>закрытый</span>
            @endif

            @endif
        </div>
        </div>
        


        @if($room->photo)
        <div>
            <img src="{{ asset('storage/rooms/' . $room->photo) }}">
        </div>
        @endif

        <p>{{ $room->description }}</p>

        
        
        
    </div>
    @endforeach
</div>  
</div>


@endsection