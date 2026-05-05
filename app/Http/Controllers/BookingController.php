<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Training;
use App\Models\TrainingBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function book(Request $request, Training $training)
    {
        $user = Auth::user();

        if (!$user || !$user->isUser()) {
            return back()->with('error', 'записаться может только пользователь');
        }

        if (!empty($training->is_cancelled)) {
            return back()->with('error', 'эта тренировка отменена');
        }

        $bookableType = (string) $request->input('bookable_type', 'user');
        $bookableId = (int) $request->input('bookable_id', (int) $user->id);

        if (!in_array($bookableType, ['user', 'child'], true)) {
            return back()->with('error', 'некорректный участник тренировки');
        }

        if ($bookableType === 'user' && $bookableId !== (int) $user->id) {
            return back()->with('error', 'некорректный участник тренировки');
        }

        $child = null;
        if ($bookableType === 'child') {
            $child = Child::query()
                ->where('id', $bookableId)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            if (!$child) {
                return back()->with('error', 'ребёнок не найден');
            }

            // Для детей: только детская, йога, массаж, фитнес
            $allowedForChild = ['kids', 'yoga', 'massage', 'fitness'];
            if (!in_array($training->type, $allowedForChild, true)) {
                return back()->with('error', 'для ребёнка доступна запись только на детскую, йогу, массаж и фитнес');
            }
        }

        if ($bookableType === 'child') {
            $childSubscription = $child ? $child->activeSubscription : null;
            $userSubscription = $user->activeSubscription;

            // Если у ребёнка нет подходящего абонемента, используем абонемент родителя.
            $subscription = ($childSubscription && $childSubscription->isUsable())
                ? $childSubscription
                : $userSubscription;
        } else {
            $subscription = $user->activeSubscription;
        }

        if (!$subscription || !$subscription->isUsable()) {
            return redirect()
                ->route('subscriptions.choose')
                ->with('error', 'для записи на тренировку нужен активный абонемент');
        }

        if (!is_null($subscription->visits_left) && (int)$subscription->visits_left <= 0) {
            return redirect()
                ->route('subscriptions.choose')
                ->with('error', 'у вас закончились посещения по абонементу');
        }

        if ($subscription->plan && $subscription->plan->code === 'DAYTIME_MONTHLY') {
            $trainingHour = (int) Carbon::parse($training->time)->format('H');

            if ($trainingHour >= 17) {
                return back()->with('error', 'по дневному абонементу можно записываться только на тренировки до 17:00');
            }
        }

        $alreadyBooked = TrainingBooking::query()
            ->where('training_id', $training->id)
            ->where('bookable_type', $bookableType)
            ->where('bookable_id', $bookableId)
            ->where('status', 'active')
            ->exists();

        // на случай старых записей через training_user (только для себя)
        if (!$alreadyBooked && $bookableType === 'user') {
            $alreadyBooked = $training->users()
                ->where('users.id', $user->id)
                ->wherePivot('status', 'active')
                ->exists();
        }

        if ($alreadyBooked) {
            return back()->with('error', 'вы уже записаны на эту тренировку');
        }

        $sameTimeTraining = TrainingBooking::query()
            ->where('account_user_id', $user->id)
            ->where('bookable_type', $bookableType)
            ->where('bookable_id', $bookableId)
            ->where('status', 'active')
            ->whereHas('training', function ($q) use ($training) {
                $q->where('date', $training->date)
                    ->where('time', $training->time)
                    ->where('id', '!=', $training->id);
            })
            ->exists();

        // на случай старых записей через training_user (только для себя)
        if (!$sameTimeTraining && $bookableType === 'user') {
            $sameTimeTraining = $user->bookedTrainings()
                ->wherePivot('status', 'active')
                ->where('date', $training->date)
                ->where('time', $training->time)
            ->where('date', $training->date)
            ->where('time', $training->time)
            ->where('trainings.id', '!=', $training->id)
            ->exists();
        }

        if ($sameTimeTraining) {
            return back()->with('error', 'нельзя записаться больше чем на одну тренировку в одно и то же время');
        }

        if ($bookableType === 'user') {
            $hasCourtBookingAtSameTime = $user->courtBookings()
                ->where('status', 'active')
                ->where('date', $training->date)
                ->where('time', $training->time)
                ->exists();

            if ($hasCourtBookingAtSameTime) {
                return back()->with('error', 'у вас уже есть аренда корта в это время');
            }
        }

        $bookedCount = TrainingBooking::query()
            ->where('training_id', $training->id)
            ->where('status', 'active')
            ->count();

        // на случай старых записей через training_user
        $bookedCount += $training->users()->wherePivot('status', 'active')->count();

        if ($bookedCount >= (int)$training->persons) {
            return back()->with('error', 'свободных мест больше нет');
        }

        TrainingBooking::create([
            'training_id' => $training->id,
            'account_user_id' => $user->id,
            'bookable_type' => $bookableType,
            'bookable_id' => $bookableId,
            'subscription_id' => $subscription->id,
            'status' => 'active',
            'price' => (int) ($training->price ?? 0),
        ]);

        if (!is_null($subscription->visits_left)) {
            $subscription->visits_left = max(0, (int)$subscription->visits_left - 1);
            $subscription->save();
        }

        return back()->with('success', 'вы успешно записались на тренировку');
    }

    public function cancel(Request $request, Training $training)
    {
        $user = Auth::user();

        if (!$user || !$user->isUser()) {
            return back()->with('error', 'отмена недоступна');
        }

        $bookableType = (string) $request->input('bookable_type', 'user');
        $bookableId = (int) $request->input('bookable_id', (int) $user->id);

        $booking = TrainingBooking::query()
            ->where('training_id', $training->id)
            ->where('account_user_id', $user->id)
            ->where('bookable_type', $bookableType)
            ->where('bookable_id', $bookableId)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if ($booking) {
            $booking->status = 'cancelled';
            $booking->save();

            $subscription = $booking->subscription;
            if ($subscription && !is_null($subscription->visits_left)) {
                $subscription->visits_left = (int) $subscription->visits_left + 1;
                $subscription->save();
            }
        } else {
            // fallback: старые записи "на себя" через training_user
            if ($bookableType !== 'user' || $bookableId !== (int) $user->id) {
                return back()->with('error', 'активная запись не найдена');
            }

            $pivotUser = $training->users()->where('users.id', $user->id)->first();
            if (!$pivotUser || !$pivotUser->pivot || $pivotUser->pivot->status !== 'active') {
                return back()->with('error', 'активная запись не найдена');
            }

            $training->users()->updateExistingPivot($user->id, [
                'status' => 'cancelled',
            ]);

            $subscription = $user->activeSubscription;
            if ($subscription && !is_null($subscription->visits_left)) {
                $subscription->visits_left = (int) $subscription->visits_left + 1;
                $subscription->save();
            }
        }

        return back()->with('success', 'запись на тренировку отменена');
    }
}