<?php

namespace App\Http\Controllers;

use App\Models\Training;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function book(Training $training)
    {
        if (!Auth::check() || !Auth::user()->isUser()) {
            return redirect()->back()->with('error', 'Только пользователь может записаться.');
        }

        if ($training->is_cancelled) {
            return redirect()->back()->with('error', 'Тренировка отменена.');
        }

        $bookedCount = $training->users()->wherePivot('status', 'active')->count();
        $totalSeats = (int) $training->persons;
        $freeSeats = $totalSeats - $bookedCount;

        if ($freeSeats <= 0) {
            return redirect()->back()->with('error', 'Нет свободных мест.');
        }

        $userId = Auth::id();
        $existing = $training->users()->where('users.id', $userId)->first();

        if ($existing) {
            // если был cancelled — активируем снова
            $training->users()->updateExistingPivot($userId, [
                'status' => 'active',
                'price' => (int)$training->price,
            ]);
        } else {
            $training->users()->attach($userId, [
                'status' => 'active',
                'price' => (int)$training->price,
            ]);
        }

        return redirect()->back()->with('success', 'Вы записаны на тренировку.');
    }

    public function cancel(Training $training)
    {
        if (!Auth::check() || !Auth::user()->isUser()) {
            return redirect()->back()->with('error', 'Только пользователь может отменить запись.');
        }

        $userId = Auth::id();
        $existing = $training->users()->where('users.id', $userId)->first();

        if (!$existing || $existing->pivot->status !== 'active') {
            return redirect()->back()->with('error', 'Вы не записаны на эту тренировку.');
        }

        $training->users()->updateExistingPivot($userId, [
            'status' => 'cancelled',
        ]);

        return redirect()->back()->with('success', 'Запись отменена.');
    }
}
