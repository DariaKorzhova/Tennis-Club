<?php

namespace App\Http\Controllers;

use App\Models\Training;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function book(Training $training)
    {
        $user = Auth::user();

        if (!$user || !$user->isUser()) {
            return back()->with('error', 'Записаться может только пользователь.');
        }

        if ($training->is_cancelled) {
            return back()->with('error', 'Эта тренировка отменена.');
        }

        $alreadyBooked = $training->users()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'active')
            ->exists();

        if ($alreadyBooked) {
            return back()->with('error', 'Вы уже записаны на эту тренировку.');
        }

        $bookedCount = $training->users()->wherePivot('status', 'active')->count();
        if ($bookedCount >= (int) $training->persons) {
            return back()->with('error', 'Свободных мест больше нет.');
        }

        $sameTimeTraining = $user->bookedTrainings()
            ->wherePivot('status', 'active')
            ->where('date', $training->date)
            ->where('time', $training->time)
            ->where('trainings.id', '!=', $training->id)
            ->exists();

        if ($sameTimeTraining) {
            return back()->with('error', 'Нельзя записаться больше чем на одну тренировку в одно и то же время.');
        }

        $existing = $training->users()->where('users.id', $user->id)->first();

        if ($existing) {
            $training->users()->updateExistingPivot($user->id, [
                'status' => 'active',
                'price' => (int) ($training->price ?? 0),
            ]);
        } else {
            $training->users()->attach($user->id, [
                'status' => 'active',
                'price' => (int) ($training->price ?? 0),
            ]);
        }

        return back()->with('success', 'Вы успешно записались на тренировку.');
    }

    public function cancel(Training $training)
    {
        $user = Auth::user();

        if (!$user || !$user->isUser()) {
            return back()->with('error', 'Отмена недоступна.');
        }

        $pivotUser = $training->users()->where('users.id', $user->id)->first();

        if (!$pivotUser || !$pivotUser->pivot || $pivotUser->pivot->status !== 'active') {
            return back()->with('error', 'Активная запись не найдена.');
        }

        $training->users()->updateExistingPivot($user->id, [
            'status' => 'cancelled',
        ]);

        return back()->with('success', 'Запись на тренировку отменена.');
    }
}