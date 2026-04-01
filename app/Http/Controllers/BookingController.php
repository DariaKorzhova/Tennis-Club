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
            return back()->with('error', 'записаться может только пользователь');
        }

        $subscription = $user->activeSubscription;

        if (!$subscription || !$subscription->isUsable()) {
            return redirect()->route('subscriptions.choose')
                ->with('error', 'для записи на тренировку нужен активный абонемент');
        }

        if (!is_null($subscription->visits_left) && (int) $subscription->visits_left <= 0) {
            return redirect()->route('subscriptions.choose')
                ->with('error', 'у вас закончились посещения по абонементу');
        }

        if ($subscription->plan && $subscription->plan->code === 'DAYTIME_MONTHLY') {
            $trainingHour = (int) Carbon::parse($training->time)->format('H');

            if ($trainingHour >= 17) {
                return back()->with('error', 'по дневному абонементу можно записываться только на тренировки до 17:00');
            }
        }

        if (!empty($training->is_cancelled)) {
            return back()->with('error', 'эта тренировка отменена');
        }

        $alreadyBooked = $training->users()
            ->where('users.id', $user->id)
            ->wherePivot('status', 'active')
            ->exists();

        if ($alreadyBooked) {
            return back()->with('error', 'вы уже записаны на эту тренировку');
        }

        $sameTimeTraining = $user->bookedTrainings()
            ->wherePivot('status', 'active')
            ->where('date', $training->date)
            ->where('time', $training->time)
            ->where('trainings.id', '!=', $training->id)
            ->exists();

        if ($sameTimeTraining) {
            return back()->with('error', 'нельзя записаться больше чем на одну тренировку в одно и то же время');
        }

        $hasCourtBookingAtSameTime = $user->courtBookings()
            ->where('status', 'active')
            ->where('date', $training->date)
            ->where('time', $training->time)
            ->exists();

        if ($hasCourtBookingAtSameTime) {
            return back()->with('error', 'у вас уже есть аренда корта в это время');
        }

        $bookedCount = $training->users()->wherePivot('status', 'active')->count();

        if ($bookedCount >= (int) $training->persons) {
            return back()->with('error', 'свободных мест больше нет');
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

        if (!is_null($subscription->visits_left)) {
            $subscription->visits_left = max(0, (int) $subscription->visits_left - 1);
            $subscription->save();
        }

        return back()->with('success', 'вы успешно записались на тренировку');
    }

    public function cancel(Training $training)
    {
        $user = Auth::user();

        if (!$user || !$user->isUser()) {
            return back()->with('error', 'отмена недоступна');
        }

        $pivotUser = $training->users()->where('users.id', $user->id)->first();

        if (!$pivotUser || !$pivotUser->pivot || $pivotUser->pivot->status !== 'active') {
            return back()->with('error', 'активная запись не найдена');
        }

        $training->users()->updateExistingPivot($user->id, [
            'status' => 'cancelled',
        ]);

        $subscription = $user->activeSubscription;

if ($subscription) {
    $subscription->loadMissing('plan');
}

if (!$subscription || !$subscription->isUsable()) {
    return redirect()->route('subscriptions.choose')
        ->with('error', 'для записи на тренировку нужен активный абонемент');
}

        return back()->with('success', 'запись на тренировку отменена');
    }
}