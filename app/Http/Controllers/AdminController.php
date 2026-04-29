<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Training;
use App\Models\Room;
use App\Models\CancellationRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function showPanel()
    {
        return $this->index();
    }

    public function updateUserRole(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:user,admin,trainer',
            'specialization' => 'nullable|in:none,tennis_trainer,fitness_trainer,yoga_trainer,masseur'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', 'Некорректные данные');
        }

        $user->role = $request->role;

        if ($request->role === 'trainer' && $request->filled('specialization')) {
            $user->specialization = $request->specialization;
        } elseif ($request->role !== 'trainer') {
            $user->specialization = 'none';
        }

        $user->save();

        return redirect()->back()->with('success', 'Роль пользователя обновлена');
    }

    public function createTraining()
    {
        $trainers = User::where('role', 'trainer')->orderBy('name')->get();
        $rooms = Room::orderBy('type')->orderBy('name')->get();

        $types = $this->getTypes();

        $timeOptions = $this->getTimeOptions();
        $durationOptions = $this->getDurationOptions();

        return view('admin.trainings_create', compact('trainers', 'rooms', 'types', 'timeOptions', 'durationOptions'));
    }

    /**
     * AJAX: доступные комнаты/тренеры/времена.
     * Учитывает:
     * - тип -> специализация тренера
     * - тип -> типы помещений
     * - занятость помещения на date+time
     * - занятость тренера на date+time
     * - свободные слоты на дату (по room_id и/или trainer_id)
     */
    public function availability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'time' => 'nullable|string',
            'type' => 'required|in:individual,split,kids,group,fitness,yoga,massage',
            'room_id' => 'nullable|integer|exists:rooms,id',
            'trainer_id' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['ok' => false, 'message' => 'Bad request'], 422);
        }

        $date = Carbon::parse($request->date)->format('Y-m-d');
        $time = $request->filled('time') ? $this->normalizeTime($request->time) : null;
        $type = $request->type;

        $rules = $this->getPersonsRules($type);

        // 1) Комнаты по типу
        $allowedRoomTypes = $this->getAllowedRoomTypesForType($type);

        $roomsQuery = Room::query()
            ->whereIn('type', $allowedRoomTypes)
            ->orderBy('type')
            ->orderBy('name');

        // 2) Если задано date+time — убираем занятые комнаты
        if ($time) {
            $busyRoomIds = Training::query()
                ->where('date', $date)
                ->where('time', $time)
                ->where('is_cancelled', false)
                ->with('rooms:id')
                ->get()
                ->flatMap(function ($t) { return $t->rooms->pluck('id'); })
                ->unique()
                ->values()
                ->all();

            if (!empty($busyRoomIds)) {
                $roomsQuery->whereNotIn('id', $busyRoomIds);
            }
        }

        $rooms = $roomsQuery->get(['id', 'name', 'type']);

        // 3) Тренеры по специализации
        $needSpec = $this->getSpecializationForType($type);

        $trainersQuery = User::query()
            ->where('role', 'trainer')
            ->where('specialization', $needSpec)
            ->orderBy('name');

        // 4) Если задано date+time — убираем занятых тренеров
        if ($time) {
            $busyTrainerIds = Training::query()
                ->where('date', $date)
                ->where('time', $time)
                ->where('is_cancelled', false)
                ->whereNotNull('trainer_id')
                ->pluck('trainer_id')
                ->unique()
                ->values()
                ->all();

            if (!empty($busyTrainerIds)) {
                $trainersQuery->whereNotIn('id', $busyTrainerIds);
            }
        }

        $trainers = $trainersQuery->get(['id', 'name', 'specialization']);

        // 5) Доступные времена на дату с учётом room_id / trainer_id
        $timeOptions = $this->getTimeOptions();

        $busyTimes = [];

        if ($request->filled('room_id')) {
            $roomId = (int)$request->room_id;

            $busyByRoom = Training::query()
                ->where('date', $date)
                ->where('is_cancelled', false)
                ->whereHas('rooms', function ($q) use ($roomId) {
                    $q->where('rooms.id', $roomId);
                })
                ->pluck('time')
                ->map(function ($t) { return Carbon::parse($t)->format('H:i'); })
                ->unique()
                ->values()
                ->all();

            $busyTimes = array_merge($busyTimes, $busyByRoom);
        }

        if ($request->filled('trainer_id')) {
            $trainerId = (int)$request->trainer_id;

            $busyByTrainer = Training::query()
                ->where('date', $date)
                ->where('is_cancelled', false)
                ->where('trainer_id', $trainerId)
                ->pluck('time')
                ->map(function ($t) { return Carbon::parse($t)->format('H:i'); })
                ->unique()
                ->values()
                ->all();

            $busyTimes = array_merge($busyTimes, $busyByTrainer);
        }

        $busyTimes = array_values(array_unique($busyTimes));

        $availableTimes = array_values(array_filter($timeOptions, function ($t) use ($busyTimes) {
            return !in_array($t, $busyTimes, true);
        }));

        return response()->json([
            'ok' => true,
            'rooms' => $rooms,
            'trainers' => $trainers->map(function ($tr) {
                return [
                    'id' => $tr->id,
                    'name' => $tr->name,
                    'specialization' => $tr->specialization,
                    'specialization_name' => $tr->specialization_name,
                ];
            })->values(),
            'timeOptions' => $availableTimes,
            'persons' => $rules,
        ]);
    }

    public function storeTraining(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'time' => 'required|string',
            'duration' => 'required|in:1 час,1.5 часа,2 часа',
            'type' => 'required|in:individual,split,kids,group,fitness,yoga,massage',
            'persons' => 'required|integer|min:1|max:200',
            'price' => 'required|integer|min:1000|max:1000000',
            'trainer_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', 'Проверь данные формы.')->withInput();
        }

        $date = Carbon::parse($request->date)->format('Y-m-d');
        $time = $this->normalizeTime($request->time);
        $type = $request->type;

        // тренер обязателен
        $trainer = User::where('id', $request->trainer_id)->where('role', 'trainer')->first();
        if (!$trainer) {
            return redirect()->back()->with('error', 'Нужно выбрать тренера.')->withInput();
        }

        // спец под тип
        $needSpec = $this->getSpecializationForType($type);
        if ($trainer->specialization !== $needSpec) {
            return redirect()->back()->with('error', 'Выбранный тренер не подходит по специализации к типу тренировки.')->withInput();
        }

        $room = Room::find($request->room_id);
        if (!$room) {
            return redirect()->back()->with('error', 'Помещение не найдено.')->withInput();
        }

        // помещение под тип
        $allowedRoomTypes = $this->getAllowedRoomTypesForType($type);
        if (!in_array($room->type, $allowedRoomTypes, true)) {
            return redirect()->back()->with('error', 'Для выбранного типа тренировки нельзя выбрать это помещение.')->withInput();
        }

        // persons правила
        $persons = (int)$request->persons;
        $personsRules = $this->getPersonsRules($type);

        if ($personsRules['fixed'] !== null) {
            $persons = (int)$personsRules['fixed'];
        } else {
            if ($persons < (int)$personsRules['min'] || $persons > (int)$personsRules['max']) {
                return redirect()->back()->with('error', 'Некорректное количество мест для выбранного типа.')->withInput();
            }
        }

        // room конфликт
        $roomBusy = Training::query()
            ->where('date', $date)
            ->where('time', $time)
            ->where('is_cancelled', false)
            ->whereHas('rooms', function ($q) use ($room) {
                $q->where('rooms.id', $room->id);
            })
            ->exists();

        if ($roomBusy) {
            return redirect()->back()->with('error', 'В этом помещении на выбранные дату и время уже есть тренировка.')->withInput();
        }

        // trainer конфликт
        $trainerBusy = Training::query()
            ->where('date', $date)
            ->where('time', $time)
            ->where('is_cancelled', false)
            ->where('trainer_id', $trainer->id)
            ->exists();

        if ($trainerBusy) {
            return redirect()->back()->with('error', 'У тренера уже есть тренировка на выбранные дату и время.')->withInput();
        }

        $training = Training::create([
            'date' => $date,
            'time' => $time,
            'duration' => $request->duration,
            'type' => $type,
            'persons' => $persons,
            'price' => (int)$request->price,
            'trainer_id' => (int)$trainer->id,
            'room_type' => $room->type,
            'is_cancelled' => false,
        ]);

        $training->rooms()->sync([$room->id]);

        // удобно: если добавляли из модалки — останешься на календаре
        return redirect()->back()->with('success', 'Тренировка добавлена.');
    }

    public function cancellations()
    {
        $requests = CancellationRequest::with(['training.rooms', 'trainer'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.cancellations', compact('requests'));
    }

    public function approveCancellation(CancellationRequest $requestModel)
    {
        $requestModel->status = 'approved';
        $requestModel->save();

        $training = $requestModel->training;
        if ($training) {
            $training->is_cancelled = true;
            $training->save();
        }

        return redirect()->back()->with('success', 'Отмена подтверждена.');
    }

    public function rejectCancellation(Request $request, CancellationRequest $requestModel)
    {
        $requestModel->status = 'rejected';
        $requestModel->admin_comment = $request->input('admin_comment');
        $requestModel->save();

        return redirect()->back()->with('success', 'Отмена отклонена.');
    }

    private function normalizeTime(string $time): string
    {
        $time = trim($time);
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }
        return $time;
    }

    private function getTypes(): array
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

    private function getTimeOptions(): array
    {
        $out = [];
        for ($h = 8; $h <= 22; $h++) {
            $out[] = sprintf('%02d:00', $h);
        }
        return $out;
    }

    private function getDurationOptions(): array
    {
        return ['1 час', '1.5 часа', '2 часа'];
    }

    private function getSpecializationForType(string $type): string
    {
        $typeToSpec = [
            'individual' => 'tennis_trainer',
            'split' => 'tennis_trainer',
            'kids' => 'tennis_trainer',
            'group' => 'tennis_trainer',
            'fitness' => 'fitness_trainer',
            'yoga' => 'yoga_trainer',
            'massage' => 'masseur',
        ];
        return $typeToSpec[$type] ?? 'tennis_trainer';
    }

    private function getAllowedRoomTypesForType(string $type): array
    {
        switch ($type) {
            case 'yoga':
                return ['yoga_hall'];
            case 'massage':
                return ['massage_room'];
            case 'fitness':
                return ['gym', 'group_hall'];
            case 'group':
                return ['group_hall'];
            case 'individual':
            case 'split':
            case 'kids':
            default:
                return ['tennis_court'];
        }
    }

    private function getPersonsRules(string $type): array
    {
        if (in_array($type, ['individual', 'kids', 'massage'], true)) {
            return ['fixed' => 1, 'min' => 1, 'max' => 1];
        }
        if ($type === 'split') {
            return ['fixed' => 2, 'min' => 2, 'max' => 2];
        }
        if ($type === 'group') {
            return ['fixed' => null, 'min' => 3, 'max' => 14];
        }
        return ['fixed' => null, 'min' => 1, 'max' => 20];
    }

    public function index()
{
    $users = User::orderBy('created_at', 'desc')->get();

    $rooms = Room::orderBy('type')
        ->orderBy('name')
        ->get();

    $trainings = Training::with(['trainer', 'rooms'])
        ->orderBy('date', 'desc')
        ->orderBy('time')
        ->get();

    $roles = [
        'user' => 'Пользователь',
        'admin' => 'Администратор',
        'trainer' => 'Тренер',
    ];

    $specializations = [
        'none' => 'Не указано',
        'tennis_trainer' => 'Тренер по теннису',
        'fitness_trainer' => 'Тренер по фитнесу',
        'yoga_trainer' => 'Тренер по йоге',
        'masseur' => 'Массажист',
    ];

    $typeNames = [
        'individual' => 'Индивидуальная',
        'split' => 'Сплит',
        'kids' => 'Детская',
        'group' => 'Групповая',
        'fitness' => 'Фитнес',
        'yoga' => 'Йога',
        'massage' => 'Массаж',
    ];

    $roomTypeNames = [
        'tennis_court' => 'Теннисный корт',
        'yoga_hall' => 'Зал йоги',
        'gym' => 'Тренажёрный зал',
        'group_hall' => 'Групповой зал',
        'massage_room' => 'Массажный кабинет',
    ];

    return view('admin.index', compact(
        'users',
        'rooms',
        'trainings',
        'roles',
        'specializations',
        'typeNames',
        'roomTypeNames'
    ));
}
}
