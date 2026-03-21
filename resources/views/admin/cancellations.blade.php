@extends('layouts.app')

@section('title', 'Запросы отмены')

@section('content')
<div class="container">
    <h2>Запросы отмены</h2>

    @if($requests->isEmpty())
        <div class="muted">Запросов нет.</div>
    @else
        <div class="admin-cancel-list">
            @foreach($requests as $req)
                @php
                    $t = $req->training;
                    $room = $t && $t->rooms ? $t->rooms->first() : null;
                @endphp

                <div class="cancel-item">
                    <div><strong>Статус:</strong> {{ $req->status }}</div>
                    <div><strong>Тренер:</strong> {{ $req->trainer ? $req->trainer->name : '—' }}</div>
                    <div><strong>Причина:</strong> {{ $req->reason ?: '—' }}</div>

                    @if($t)
                        <div class="mt">
                            <strong>Тренировка:</strong>
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
                                <button class="btn-card btn-card--warning" type="submit">Подтвердить отмену</button>
                            </form>

                            <form method="POST" action="{{ route('admin.cancellations.reject', $req->id) }}">
                                @csrf
                                <input class="input-mini" type="text" name="admin_comment" placeholder="Комментарий (опционально)">
                                <button class="btn-card btn-card--danger" type="submit">Отклонить</button>
                            </form>
                        </div>
                    @else
                        <div class="muted">Обработано. Комментарий: {{ $req->admin_comment ?: '—' }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
