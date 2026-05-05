@extends('layouts.app')

@section('title', 'все абонементы')

@section('content')
<div class="container">
    <div class="account-layout-edit">
        <div class="account-content">
            <div class="account-edit-shell">
                <a href="{{ route('account') }}#account-subscription" class="account-back-btn">← назад</a>
                <div class="account-edit-card">
                    <h2 class="account-edit-title">все абонементы</h2>

                    @if($subscriptions->isEmpty())
                        <div class="muted">у вас пока нет оформленных абонементов.</div>
                        <a href="{{ route('subscriptions.choose') }}" class="account-edit-btn" style="margin-top:12px;display:inline-flex;">оформить абонемент</a>
                    @else
                        <div class="users-table-container">
                            <table class="users-table">
                                <thead>
                                    <tr>
                                        <th>тариф</th>
                                        <th>статус</th>
                                        <th>период</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscriptions as $sub)
                                        @php
                                            $sc = 'account-subscription-badge--pending';
                                            if ($sub->status === 'active') {
                                                $sc = 'account-subscription-badge--active';
                                            } elseif ($sub->status === 'payment_overdue') {
                                                $sc = 'account-subscription-badge--overdue';
                                            }
                                        @endphp
                                        <tr>
                                            <td>{{ optional($sub->plan)->name ?? '—' }}</td>
                                            <td>
                                                <span class="account-subscription-badge {{ $sc }}">{{ $sub->status_label }}</span>
                                            </td>
                                            <td>
                                                {{ optional($sub->start_date)->format('d.m.Y') ?? '—' }}
                                                —
                                                {{ optional($sub->end_date)->format('d.m.Y') ?? '—' }}
                                            </td>
                                            <td>
                                                <a href="{{ route('subscriptions.history', ['subscription_id' => $sub->id]) }}" class="account-edit-btn" style="padding:8px 12px;font-size:14px;">история</a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
