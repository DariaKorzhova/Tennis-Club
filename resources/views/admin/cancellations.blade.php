@extends('layouts.app')

@section('title', 'запросы отмены')

@section('content')
<div class="container">
    <h2>запросы отмены</h2>

    @if($requests->isEmpty())
        <div class="muted">запросов нет.</div>
    @else
        <div class="admin-cancel-list">
            @foreach($requests as $req)
                @php
                    $t = $req->training;
                    $room = $t && $t->rooms ? $t->rooms->first() : null;
                @endphp

                <div class="cancel-item">
                    <div><strong>статус:</strong> {{ $req->status }}</div>
                    <div><strong>тренер:</strong> {{ $req->trainer ? $req->trainer->name : '—' }}</div>
                    <div><strong>причина:</strong> {{ $req->reason ?: '—' }}</div>

                    @if($t)
                        <div class="mt">
                            <strong>тренировка:</strong>
                            ID {{ $t->id }},
                            {{ $t->type }},
                            {{ $t->duration }},
                            цена {{ (int)$t->price }} ₽,
                            мест {{ (int)$t->persons }},
                            @if($room) место: {{ $room->name }} @endif
                        </div>
                    @endif

                    @if($req->status === 'pending')
                        <div class="btn-row">
                            <form method="POST" action="{{ route('admin.cancellations.approve', $req->id) }}">
                                @csrf
                                <button class="btn-card btn-card--warning" type="submit">подтвердить отмену</button>
                            </form>

                            <form method="POST" action="{{ route('admin.cancellations.reject', $req->id) }}">
                                @csrf
                                <input class="input-mini" type="text" name="admin_comment" placeholder="комментарий (опционально)">
                                <button class="btn-card btn-card--danger" type="submit">отклонить</button>
                            </form>
                        </div>
                    @else
                        <div class="muted">обработано. комментарий: {{ $req->admin_comment ?: '—' }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
