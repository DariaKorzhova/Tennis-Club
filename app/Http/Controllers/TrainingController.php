<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\Room;
use App\Models\CancellationRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainingController extends Controller
{
    public function showTraining(Request $request)
    {
        $dayOffset = (int) $request->input('week', 0);
        if ($dayOffset < 0) {
            $dayOffset = 0;
        }
        if ($dayOffset > 3) {
            $dayOffset = 3;
        }

        $selectedType = $request->input('type', 'all');
        $selectedRoom = $request->input('room', 'all');

        // Календарь строится относительно сегодняшней даты:
        // 0 => сегодня + 6 дней, 1 => с 7-го по 13-й день и т.д.
        $startDate = Carbon::today()->addDays($dayOffset * 7)->startOfDay();
        $endDate = $startDate->copy()->addDays(6)->endOfDay();

        $query = Training::with(['rooms', 'trainer'])
            ->whereBetween('date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ]);

        if ($selectedType !== 'all') {
            $query->where('type', $selectedType);
        }

        if ($selectedRoom !== 'all') {
            $query->whereHas('rooms', function ($q) use ($selectedRoom) {
                $q->where('name', $selectedRoom);
            });
        }

        $trainings = $query
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->get();

        $calendarData = $this->prepareCalendarData($trainings, $startDate);

        $types = $this->getTypes();
        $rooms = $this->getRooms();
        $typeColors = $this->getTypeColors();

        return view('trainings.trainings', compact(
            'calendarData',
            'types',
            'rooms',
            'selectedType',
            'selectedRoom',
            'typeColors',
            'dayOffset'
        ));
    }

    private function prepareCalendarData($trainings, Carbon $startDate)
    {
        $today = Carbon::today();
        $dayNames = [
            1 => 'ПН',
            2 => 'ВТ',
            3 => 'СР',
            4 => 'ЧТ',
            5 => 'ПТ',
            6 => 'СБ',
            7 => 'ВС',
        ];

        $timeSlots = [];
        for ($hour = 8; $hour <= 22; $hour++) {
            $timeSlots[] = sprintf('%02d:00', $hour);
        }

        $calendar = [
            'days' => [],
            'times' => $timeSlots,
        ];

        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);

            $calendar['days'][] = [
                'name' => $dayNames[$date->isoWeekday()] ?? $date->format('D'),
                'date' => $date->format('Y-m-d'),
                'dateFormatted' => $date->format('d.m'),
                'isToday' => $date->isSameDay($today),
                'trainings' => [],
            ];
        }

        $types = $this->getTypes();
        $typeColors = $this->getTypeColors();

        $authUser = Auth::user();
        $authId = Auth::id();

        foreach ($trainings as $training) {
            $trainingDate = Carbon::parse($training->date)->startOfDay();
            $dayIndex = $startDate->copy()->startOfDay()->diffInDays($trainingDate, false);

            if ($dayIndex < 0 || $dayIndex > 6) {
                continue;
            }

            $timeSlot = Carbon::parse($training->time)->format('H:00');
            $color = $typeColors[$training->type] ?? '#777777';

            $room = $training->rooms->first();
            $roomName = $room ? $room->name : 'Не указано';
            $roomId = $room ? $room->id : null;

            $trainerName = $training->trainer ? $training->trainer->full_name : 'Не назначен';
            $trainerId = $training->trainer ? $training->trainer->id : null;

            $bookedCount = $training->users()->wherePivot('status', 'active')->count();
            $totalSeats = (int) ($training->persons ?? 0);
            $freeSeats = max(0, $totalSeats - $bookedCount);

            $isBookedByMe = false;
            if ($authUser && $authUser->isUser()) {
                $pivotUser = $training->users()->where('users.id', $authId)->first();
                if ($pivotUser && $pivotUser->pivot && $pivotUser->pivot->status === 'active') {
                    $isBookedByMe = true;
                }
            }

            $hasPendingCancel = false;
            if ($authUser && $authUser->isTrainer() && (int) $training->trainer_id === (int) $authId) {
                $hasPendingCancel = CancellationRequest::where('training_id', $training->id)
                    ->where('status', 'pending')
                    ->exists();
            }

            if (!isset($calendar['days'][$dayIndex]['trainings'][$timeSlot])) {
                $calendar['days'][$dayIndex]['trainings'][$timeSlot] = [];
            }

            $calendar['days'][$dayIndex]['trainings'][$timeSlot][] = [
                'id' => $training->id,
                'duration' => $training->duration,
                'type_name' => $types[$training->type] ?? $training->type,
                'color' => $color,
                'price' => (int) ($training->price ?? 0),

                'total_seats' => $totalSeats,
                'booked_seats' => $bookedCount,
                'free_seats' => $freeSeats,
                'is_full' => ($freeSeats <= 0),

                'trainer_name' => $trainerName,
                'trainer_id' => $trainerId,
                'trainer_url' => $trainerId ? route('trainers.show', $trainerId) : null,

                'room_name' => $roomName,
                'room_id' => $roomId,
                'room_url' => $roomId ? route('rooms.view', $roomId) : null,

                'is_cancelled' => (bool) $training->is_cancelled,
                'is_booked_by_me' => $isBookedByMe,
                'has_pending_cancel' => $hasPendingCancel,

                'book_url' => route('trainings.book', $training->id),
                'cancel_url' => route('trainings.cancel', $training->id),
                'request_cancel_url' => route('trainings.request_cancel', $training->id),

                'date_formatted' => Carbon::parse($training->date)->format('d.m.Y'),
                'time_formatted' => Carbon::parse($training->time)->format('H:i'),

                'can_cancel_request' => (
                    $authUser
                    && $authUser->isTrainer()
                    && (int) $training->trainer_id === (int) $authId
                ),
            ];
        }

        return $calendar;
    }

    private function getTypes()
    {
        return [
            'individual' => 'Индивидуальная',
            'split' => 'Сплит',
            'kids' => 'Детская',
            'group' => 'Групповая',
            'fitness' => 'Фитнес',
            'yoga' => 'Йога',
            'massage' => 'Массаж'
        ];
    }

    private function getRooms()
    {
        $rooms = Room::orderBy('type')->orderBy('name')->get();
        $roomList = ['all' => 'Все помещения'];
        foreach ($rooms as $room) {
            $roomList[$room->name] = $room->name;
        }
        return $roomList;
    }

    private function getTypeColors()
    {
        return [
            'individual' => '#996016',
            'split' => '#5f9414',
            'kids' => '#2e9b00',
            'group' => '#18a000',
            'fitness' => '#2196F3',
            'yoga' => '#9C27B0',
            'massage' => '#FF9800'
        ];
    }
}