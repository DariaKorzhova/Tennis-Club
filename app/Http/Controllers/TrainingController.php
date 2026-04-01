<?php

namespace App\Http\Controllers;

use App\Models\Training;
use App\Models\Room;
use App\Models\User;
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
        $selectedTrainer = (string) $request->input('trainer', 'all');

        $startDate = Carbon::today()->addDays($dayOffset * 7)->startOfDay();
        $endDate = $startDate->copy()->addDays(6)->endOfDay();

        $authUser = Auth::user();
        $authId = Auth::id();

        $trainings = Training::with(['rooms', 'trainer', 'users'])
            ->whereBetween('date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ])
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->get();

        $myCourtBookings = collect();

        if ($authUser && $authUser->isUser()) {
            $myCourtBookings = $authUser->courtBookings()
                ->with('room')
                ->where('status', 'active')
                ->whereBetween('date', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                ])
                ->get();
        }

        $calendarData = $this->prepareCalendarData(
            $trainings,
            $startDate,
            $selectedType,
            $selectedRoom,
            $selectedTrainer,
            $authUser,
            $authId,
            $myCourtBookings
        );

        $types = $this->getTypes();
        $rooms = $this->getRooms();
        $trainers = $this->getTrainers();
        $typeColors = $this->getTypeColors();

        return view('trainings.trainings', compact(
            'calendarData',
            'types',
            'rooms',
            'trainers',
            'selectedType',
            'selectedRoom',
            'selectedTrainer',
            'typeColors',
            'dayOffset'
        ));
    }

    private function prepareCalendarData(
        $trainings,
        Carbon $startDate,
        $selectedType,
        $selectedRoom,
        $selectedTrainer,
        $authUser,
        $authId,
        $myCourtBookings = null
    ) {
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

        $myCourtBookingsMap = [];

        if ($myCourtBookings) {
            foreach ($myCourtBookings as $booking) {
                $slotDate = $booking->date;
                $slotTime = Carbon::parse($booking->time)->format('H:00');

                $myCourtBookingsMap[$slotDate][$slotTime] = [
                    'room_name' => optional($booking->room)->name ?? 'корт',
                    'date_formatted' => Carbon::parse($booking->date)->format('d.m.Y'),
                    'time_formatted' => Carbon::parse($booking->time)->format('H:i'),
                ];
            }
        }

        foreach ($trainings as $training) {
            $trainingDate = Carbon::parse($training->date)->startOfDay();
            $dayIndex = $startDate->copy()->startOfDay()->diffInDays($trainingDate, false);

            if ($dayIndex < 0 || $dayIndex > 6) {
                continue;
            }

            $timeSlot = Carbon::parse($training->time)->format('H:00');

            $room = $training->rooms->first();
            $roomName = $room ? $room->name : 'не указано';
            $roomId = $room ? $room->id : null;

            $trainerName = $training->trainer ? $training->trainer->full_name : 'не назначен';
            $trainerId = $training->trainer ? $training->trainer->id : null;

            $isBookedByMe = false;
            $hasOtherTrainingAtSameTime = false;
            $hasCourtBookingAtSameTime = false;
            $courtBookingInfo = null;

            if ($authUser && $authUser->isUser()) {
                foreach ($training->users as $u) {
                    if ((int) $u->id === (int) $authId && $u->pivot && $u->pivot->status === 'active') {
                        $isBookedByMe = true;
                        break;
                    }
                }

                $hasOtherTrainingAtSameTime = $authUser->bookedTrainings()
                    ->wherePivot('status', 'active')
                    ->where('date', $training->date)
                    ->where('time', $training->time)
                    ->where('trainings.id', '!=', $training->id)
                    ->exists();

                if (isset($myCourtBookingsMap[$training->date][$timeSlot])) {
                    $hasCourtBookingAtSameTime = true;
                    $courtBookingInfo = $myCourtBookingsMap[$training->date][$timeSlot];
                }
            }

            $matchesFilters = true;

            if ($selectedType !== 'all' && $training->type !== $selectedType) {
                $matchesFilters = false;
            }

            if ($selectedRoom !== 'all' && $roomName !== $selectedRoom) {
                $matchesFilters = false;
            }

            if ($selectedTrainer !== 'all' && (string) $trainerId !== (string) $selectedTrainer) {
                $matchesFilters = false;
            }

            if (!$matchesFilters && !$isBookedByMe) {
                continue;
            }

            $color = $typeColors[$training->type] ?? '#777777';

            $bookedCount = $training->users()->wherePivot('status', 'active')->count();
            $totalSeats = (int) ($training->persons ?? 0);
            $freeSeats = max(0, $totalSeats - $bookedCount);

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
                'has_other_training_at_same_time' => $hasOtherTrainingAtSameTime,
                'has_court_booking_at_same_time' => $hasCourtBookingAtSameTime,
                'court_booking_info' => $courtBookingInfo,

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

                'is_my_court_booking' => false,
            ];
        }

        if ($authUser && $authUser->isUser() && $myCourtBookings) {
            foreach ($myCourtBookings as $booking) {
                $bookingDate = Carbon::parse($booking->date)->startOfDay();
                $dayIndex = $startDate->copy()->startOfDay()->diffInDays($bookingDate, false);

                if ($dayIndex < 0 || $dayIndex > 6) {
                    continue;
                }

                $timeSlot = Carbon::parse($booking->time)->format('H:00');

                if (!isset($calendar['days'][$dayIndex]['trainings'][$timeSlot])) {
                    $calendar['days'][$dayIndex]['trainings'][$timeSlot] = [];
                }

                $alreadyHasRentalCard = collect($calendar['days'][$dayIndex]['trainings'][$timeSlot])
                    ->contains(function ($item) {
                        return !empty($item['is_my_court_booking']);
                    });

                if (!$alreadyHasRentalCard) {
                    $calendar['days'][$dayIndex]['trainings'][$timeSlot][] = [
                        'id' => 'court_booking_' . $booking->id,
                        'duration' => $booking->duration ?? '1 час',
                        'type_name' => 'аренда',
                        'color' => '#111111',
                        'price' => (int) ($booking->price ?? 0),

                        'total_seats' => 0,
                        'booked_seats' => 0,
                        'free_seats' => 0,
                        'is_full' => false,

                        'trainer_name' => '—',
                        'trainer_id' => null,
                        'trainer_url' => null,

                        'room_name' => optional($booking->room)->name ?? 'корт',
                        'room_id' => $booking->room_id ?? null,
                        'room_url' => !empty($booking->room_id) ? route('rooms.view', $booking->room_id) : null,

                        'is_cancelled' => false,
                        'is_booked_by_me' => false,
                        'has_pending_cancel' => false,
                        'has_other_training_at_same_time' => true,
                        'has_court_booking_at_same_time' => false,

                        'book_url' => null,
                        'cancel_url' => null,
                        'request_cancel_url' => null,

                        'date_formatted' => Carbon::parse($booking->date)->format('d.m.Y'),
                        'time_formatted' => Carbon::parse($booking->time)->format('H:i'),

                        'can_cancel_request' => false,
                        'is_my_court_booking' => true,
                        'court_booking_info' => [
                            'room_name' => optional($booking->room)->name ?? 'корт',
                            'date_formatted' => Carbon::parse($booking->date)->format('d.m.Y'),
                            'time_formatted' => Carbon::parse($booking->time)->format('H:i'),
                        ],
                    ];
                }
            }
        }

        return $calendar;
    }

    private function getTypes()
    {
        return [
            'individual' => 'индивидуальная',
            'split' => 'сплит',
            'kids' => 'детская',
            'group' => 'групповая',
            'fitness' => 'фитнес',
            'yoga' => 'йога',
            'massage' => 'массаж',
        ];
    }

    private function getRooms()
    {
        $rooms = Room::orderBy('type')->orderBy('name')->get();
        $roomList = ['all' => 'все помещения'];

        foreach ($rooms as $room) {
            $roomList[$room->name] = $room->name;
        }

        return $roomList;
    }

    private function getTrainers()
    {
        $trainers = User::where('role', 'trainer')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $trainerList = ['all' => 'все тренеры'];

        foreach ($trainers as $trainer) {
            $trainerList[$trainer->id] = $trainer->full_name;
        }

        return $trainerList;
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
            'massage' => '#FF9800',
        ];
    }
}