<?php

namespace App\Http\Controllers;

use App\Models\CourtBooking;
use App\Models\Room;
use App\Models\Training;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourtBookingController extends Controller
{
    public function index(Request $request)
    {
        $dayOffset = (int) $request->input('week', 0);
        if ($dayOffset < 0) $dayOffset = 0;
        if ($dayOffset > 3) $dayOffset = 3;

        $allCourts = Room::where('type', 'tennis_court')
            ->orderBy('name')
            ->get();

        $selectedCourtId = (int) $request->input('room_id', 0);

        if ($selectedCourtId <= 0 && $allCourts->isNotEmpty()) {
            $selectedCourtId = (int) $allCourts->first()->id;
        }

        $selectedCourt = $allCourts->firstWhere('id', $selectedCourtId);

        $startDate = Carbon::today()->addDays($dayOffset * 7)->startOfDay();
        $endDate = $startDate->copy()->addDays(6)->endOfDay();

        $bookings = collect();
        $courtTrainings = collect();
        $myTrainings = collect();

        if ($selectedCourt) {
            $bookings = CourtBooking::with(['room', 'user'])
                ->where('room_id', $selectedCourt->id)
                ->where('status', 'active')
                ->whereBetween('date', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                ])
                ->get();

            // Тренировки, которые реально проходят на выбранном корте
            $courtTrainings = Training::with(['rooms', 'trainer', 'users'])
                ->whereBetween('date', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                ])
                ->whereHas('rooms', function ($q) use ($selectedCourt) {
                    $q->where('rooms.id', $selectedCourt->id);
                })
                ->get();
        }

        // ВСЕ мои тренировки за период, независимо от помещения
        if (Auth::check()) {
            $myTrainings = Auth::user()->bookedTrainings()
                ->wherePivot('status', 'active')
                ->with(['rooms', 'trainer'])
                ->whereBetween('date', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                ])
                ->get();
        }

        $calendarData = $this->prepareCalendarData(
            $selectedCourt,
            $bookings,
            $courtTrainings,
            $myTrainings,
            $startDate
        );

        return view('court_bookings.index', [
            'calendarData' => $calendarData,
            'courts' => $allCourts,
            'selectedCourtId' => $selectedCourtId,
            'selectedCourt' => $selectedCourt,
            'dayOffset' => $dayOffset,
            'pricePerHour' => 2000,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'date' => ['required', 'date'],
            'time' => ['required'],
            'persons' => ['required', 'integer', 'min:1', 'max:4'],
            'hours' => ['required', 'integer', 'min:1', 'max:2'],
        ]);

        $room = Room::findOrFail($request->room_id);
        $date = $request->date;
        $time = Carbon::parse($request->time)->format('H:i:s');
        $persons = (int) $request->persons;
        $hours = (int) $request->hours;

        $timeSlots = [$time];

        if ($hours === 2) {
            $secondSlot = Carbon::parse($time)->addHour()->format('H:i:s');
            $timeSlots[] = $secondSlot;
        }

        foreach ($timeSlots as $slot) {
            $trainingExists = Training::where('date', $date)
                ->where('time', $slot)
                ->whereHas('rooms', function ($q) use ($room) {
                    $q->where('rooms.id', $room->id);
                })
                ->exists();

            if ($trainingExists) {
                return back()->with('error', 'Корт занят тренировкой в выбранное время.');
            }

            $bookingExists = CourtBooking::where('room_id', $room->id)
                ->where('date', $date)
                ->where('time', $slot)
                ->where('status', 'active')
                ->exists();

            if ($bookingExists) {
                return back()->with('error', 'Корт уже забронирован в выбранное время.');
            }
        }

        $pricePerHour = 2000;
        $totalPrice = $pricePerHour * $hours;
        $group = uniqid('court_', true);

        foreach ($timeSlots as $slot) {
            CourtBooking::create([
                'room_id' => $room->id,
                'user_id' => Auth::id(),
                'date' => $date,
                'time' => $slot,
                'duration' => $hours === 2 ? '2 часа' : '1 час',
                'price' => $pricePerHour,
                'status' => 'active',
                'persons' => $persons,
                'booking_group' => $group,
            ]);
        }

        return back()->with('success', 'Корт успешно забронирован. Итоговая стоимость: ' . $totalPrice . ' ₽');
    }

    private function prepareCalendarData($court, $bookings, $courtTrainings, $myTrainings, Carbon $startDate)
    {
        $today = Carbon::today();

        $typeNames = [
            'individual' => 'Индивидуальная',
            'split' => 'Сплит',
            'kids' => 'Детская',
            'group' => 'Групповая',
            'fitness' => 'Фитнес',
            'yoga' => 'Йога',
            'massage' => 'Массаж',
        ];

        $dayNames = [
            1 => 'ПН',
            2 => 'ВТ',
            3 => 'СР',
            4 => 'ЧТ',
            5 => 'ПТ',
            6 => 'СБ',
            7 => 'ВС',
        ];

        $times = [];
        for ($hour = 8; $hour <= 21; $hour++) {
            $times[] = sprintf('%02d:00', $hour);
        }

        $days = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startDate->copy()->addDays($i);

            $days[] = [
                'name' => $dayNames[$date->isoWeekday()],
                'date' => $date->format('Y-m-d'),
                'dateFormatted' => $date->format('d.m'),
                'isToday' => $date->isSameDay($today),
            ];
        }

        $grid = [];

        foreach ($days as $day) {
            foreach ($times as $time) {
                $grid[$day['date']][$time] = [
                    'status' => 'free',
                    'room_id' => $court ? $court->id : null,
                    'room_name' => $court ? $court->name : '',
                    'date' => $day['date'],
                    'time' => $time,
                ];
            }
        }

        // 1. Сначала отмечаем тренировки, которые проходят именно на выбранном корте
        foreach ($courtTrainings as $training) {
            $time = Carbon::parse($training->time)->format('H:00');

            if (isset($grid[$training->date][$time])) {
                $grid[$training->date][$time] = [
                    'status' => 'training',
                    'room_id' => $court ? $court->id : null,
                    'room_name' => $court ? $court->name : '',
                    'date' => $training->date,
                    'time' => $time,
                    'training_id' => $training->id,
                    'trainer_name' => $training->trainer ? $training->trainer->full_name : 'Не назначен',
                    'type_name' => $training->type,
                    'type_label' => $typeNames[$training->type] ?? $training->type,
                ];
            }
        }

        // 2. Потом поверх отмечаем ВСЕ мои тренировки, даже если они проходят не на этом корте
        foreach ($myTrainings as $training) {
            $time = Carbon::parse($training->time)->format('H:00');

            if (isset($grid[$training->date][$time])) {
                $room = $training->rooms->first();

                $grid[$training->date][$time] = [
                    'status' => 'my_training',
                    'room_id' => $court ? $court->id : null,
                    'room_name' => $court ? $court->name : '',
                    'date' => $training->date,
                    'time' => $time,
                    'training_id' => $training->id,
                    'trainer_name' => $training->trainer ? $training->trainer->full_name : 'Не назначен',
                    'type_name' => $training->type,
                    'type_label' => $typeNames[$training->type] ?? $training->type,
                    'training_room_name' => $room ? $room->name : 'Другое помещение',
                ];
            }
        }

        // 3. Потом брони корта
        foreach ($bookings as $booking) {
            $time = Carbon::parse($booking->time)->format('H:00');

            if (isset($grid[$booking->date][$time])) {
                $grid[$booking->date][$time] = [
                    'status' => 'booked',
                    'room_id' => $booking->room_id,
                    'room_name' => $booking->room ? $booking->room->name : '',
                    'date' => $booking->date,
                    'time' => $time,
                ];
            }
        }

        return [
            'days' => $days,
            'times' => $times,
            'grid' => $grid,
        ];
    }
}