<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Training;
use App\Models\Room;
use App\Models\CancellationRequest;
use App\Models\TrainingTypeSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            return redirect()->back()->with('error', 'некорректные данные');
        }

        $user->role = $request->role;

        if ($request->role === 'trainer' && $request->filled('specialization')) {
            $user->specialization = $request->specialization;
        } elseif ($request->role !== 'trainer') {
            $user->specialization = 'none';
        }

        $user->save();

        return redirect()->back()->with('success', 'роль пользователя обновлена');
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

        // 1) Помещения, в которых разрешён выбранный тип тренировки
        $busyRoomIds = [];
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
        }

        $rooms = Room::query()
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->filter(function ($room) use ($type) {
                return Room::acceptsTrainingType($room, $type);
            })
            ->when(!empty($busyRoomIds), function ($collection) use ($busyRoomIds) {
                return $collection->reject(function ($room) use ($busyRoomIds) {
                    return in_array($room->id, $busyRoomIds, true);
                });
            })
            ->values();

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
            return redirect()->back()->with('error', 'проверь данные формы.')->withInput();
        }

        $date = Carbon::parse($request->date)->format('Y-m-d');
        $time = $this->normalizeTime($request->time);
        $type = $request->type;

        // тренер обязателен
        $trainer = User::where('id', $request->trainer_id)->where('role', 'trainer')->first();
        if (!$trainer) {
            return redirect()->back()->with('error', 'нужно выбрать тренера.')->withInput();
        }

        // спец под тип
        $needSpec = $this->getSpecializationForType($type);
        if ($trainer->specialization !== $needSpec) {
            return redirect()->back()->with('error', 'выбранный тренер не подходит по специализации к типу тренировки.')->withInput();
        }

        $room = Room::find($request->room_id);
        if (!$room) {
            return redirect()->back()->with('error', 'помещение не найдено.')->withInput();
        }

        if (!Room::acceptsTrainingType($room, $type)) {
            return redirect()->back()->with('error', 'для выбранного типа тренировки нельзя выбрать это помещение.')->withInput();
        }

        // persons правила
        $persons = (int)$request->persons;
        $personsRules = $this->getPersonsRules($type);

        if ($personsRules['fixed'] !== null) {
            $persons = (int)$personsRules['fixed'];
        } else {
            if ($persons < (int)$personsRules['min'] || $persons > (int)$personsRules['max']) {
                return redirect()->back()->with('error', 'некорректное количество мест для выбранного типа.')->withInput();
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
            return redirect()->back()->with('error', 'в этом помещении на выбранные дату и время уже есть тренировка.')->withInput();
        }

        // trainer конфликт
        $trainerBusy = Training::query()
            ->where('date', $date)
            ->where('time', $time)
            ->where('is_cancelled', false)
            ->where('trainer_id', $trainer->id)
            ->exists();

        if ($trainerBusy) {
            return redirect()->back()->with('error', 'у тренера уже есть тренировка на выбранные дату и время.')->withInput();
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
        return redirect()->back()->with('success', 'тренировка добавлена.');
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

        return redirect()->back()->with('success', 'отмена подтверждена.');
    }

    public function rejectCancellation(Request $request, CancellationRequest $requestModel)
    {
        $requestModel->status = 'rejected';
        $requestModel->admin_comment = $request->input('admin_comment');
        $requestModel->save();

        return redirect()->back()->with('success', 'отмена отклонена.');
    }

    public function updateTrainingTypeSetting(Request $request, string $type)
    {
        $typesMap = $this->getTypes();
        if (!array_key_exists($type, $typesMap)) {
            return redirect()->back()->with('error', 'неизвестный тип тренировки.');
        }

        $validator = Validator::make($request->all(), [
            'price' => 'required|integer|min:500|max:1000000',
            'persons_fixed' => 'nullable|integer|min:1|max:200',
            'persons_min' => 'required|integer|min:1|max:200',
            'persons_max' => 'required|integer|min:1|max:200',
            'trainer_ids' => 'nullable|array',
            'trainer_ids.*' => 'integer|exists:users,id',
            'weekdays' => 'nullable|array',
            'weekdays.*' => 'integer|between:1,7',
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', 'проверьте настройки типа тренировки.')->withInput();
        }

        $start = Carbon::createFromFormat('H:i', $request->time_start);
        $end = Carbon::createFromFormat('H:i', $request->time_end);
        if ($end->lessThanOrEqualTo($start)) {
            return redirect()->back()->with('error', 'время окончания должно быть позже начала.');
        }

        $trainerIds = collect($request->input('trainer_ids', []))->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        $weekdays = collect($request->input('weekdays', []))->map(fn ($d) => (int) $d)->filter(fn ($d) => $d >= 1 && $d <= 7)->unique()->sort()->values()->all();

        TrainingTypeSetting::updateOrCreate(
            ['type' => $type],
            [
                'price' => (int) $request->price,
                'persons_fixed' => $request->filled('persons_fixed') ? (int) $request->persons_fixed : null,
                'persons_min' => (int) $request->persons_min,
                'persons_max' => (int) $request->persons_max,
                'trainer_ids' => $trainerIds,
                'weekdays' => $weekdays ?: [1, 2, 3, 4, 5, 6, 7],
                'time_start' => $start->format('H:i:s'),
                'time_end' => $end->format('H:i:s'),
            ]
        );

        return redirect()->back()->with('success', 'настройки типа тренировки обновлены.');
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
            'individual' => 'индивидуальная',
            'split' => 'сплит',
            'kids' => 'детская',
            'group' => 'групповая',
            'fitness' => 'фитнес',
            'yoga' => 'йога',
            'massage' => 'массаж'
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
            'user' => 'пользователь',
            'admin' => 'администратор',
            'trainer' => 'тренер',
        ];

        $specializations = [
            'none' => 'не указано',
            'tennis_trainer' => 'тренер по теннису',
            'fitness_trainer' => 'тренер по фитнесу',
            'yoga_trainer' => 'тренер по йоге',
            'masseur' => 'массажист',
        ];

        $typeNames = $this->getTypes();
        $trainingTypes = $typeNames;

        $roomTypeNames = Room::getRoomTypes();

        $weekDayNames = [
            1 => 'пн',
            2 => 'вт',
            3 => 'ср',
            4 => 'чт',
            5 => 'пт',
            6 => 'сб',
            7 => 'вс',
        ];

        $trainingTypeSettings = [];
        foreach (TrainingTypeSetting::query()->orderBy('type')->get() as $row) {
            $trainingTypeSettings[$row->type] = [
                'price' => (int) $row->price,
                'persons_min' => (int) $row->persons_min,
                'persons_max' => (int) $row->persons_max,
                'persons_fixed' => $row->persons_fixed !== null ? (int) $row->persons_fixed : null,
                'trainer_ids' => array_map('intval', $row->trainer_ids ?: []),
                'weekdays' => $row->weekdays ?: [1, 2, 3, 4, 5, 6, 7],
                'time_start' => $row->time_start
                    ? Carbon::parse($row->time_start)->format('H:i')
                    : '08:00',
                'time_end' => $row->time_end
                    ? Carbon::parse($row->time_end)->format('H:i')
                    : '22:00',
            ];
        }

        $trainerList = User::where('role', 'trainer')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $trainersByType = [];
        foreach (array_keys($trainingTypes) as $typeKey) {
            $needSpec = $this->getSpecializationForType($typeKey);
            $trainersByType[$typeKey] = $trainerList->where('specialization', $needSpec)->values();
        }

        $defaultTypeSetting = [
            'price' => 1000,
            'persons_min' => 1,
            'persons_max' => 20,
            'persons_fixed' => null,
            'trainer_ids' => [],
            'weekdays' => [1, 2, 3, 4, 5, 6, 7],
            'time_start' => '08:00',
            'time_end' => '22:00',
        ];
        foreach (array_keys($trainingTypes) as $typeKey) {
            $trainingTypeSettings[$typeKey] = array_merge(
                $defaultTypeSetting,
                $trainingTypeSettings[$typeKey] ?? []
            );
        }

        return view('admin.index', compact(
            'users',
            'rooms',
            'trainings',
            'roles',
            'specializations',
            'typeNames',
            'trainingTypes',
            'trainingTypeSettings',
            'trainersByType',
            'weekDayNames',
            'roomTypeNames'
        ));
    }

    public function storeRoom(Request $request)
    {
        $roomTypeKeys = implode(',', array_keys(Room::getRoomTypes()));
        $allTrainingTypeKeys = implode(',', array_keys($this->getTypes()));

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:'.$roomTypeKeys,
            'description' => 'nullable|string|max:5000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'suitable_training_types' => 'nullable|array',
            'suitable_training_types.*' => 'in:'.$allTrainingTypeKeys,
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', 'проверь данные помещения.')->withInput();
        }

        $allowed = Room::allowedTrainingTypesForRoomType($request->type);
        $selected = array_values(array_intersect(
            $allowed,
            array_map('strval', $request->input('suitable_training_types', []))
        ));
        $suitable = count($selected) > 0 ? $selected : null;

        $photo = 'default.jpg';
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo')->store('rooms', 'public');
        }

        Room::create([
            'name' => $request->name,
            'type' => $request->type,
            'description' => $request->description,
            'photo' => $photo,
            'suitable_training_types' => $suitable,
        ]);

        return redirect()->back()->with('success', 'помещение добавлено.');
    }

    public function updateRoom(Request $request, Room $room)
    {
        $roomTypeKeys = implode(',', array_keys(Room::getRoomTypes()));
        $allTrainingTypeKeys = implode(',', array_keys($this->getTypes()));

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:'.$roomTypeKeys,
            'description' => 'nullable|string|max:5000',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_photo' => 'nullable|boolean',
            'suitable_training_types' => 'nullable|array',
            'suitable_training_types.*' => 'in:'.$allTrainingTypeKeys,
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', 'проверь данные помещения.')->withInput();
        }

        $allowed = Room::allowedTrainingTypesForRoomType($request->type);
        $selected = array_values(array_intersect(
            $allowed,
            array_map('strval', $request->input('suitable_training_types', []))
        ));
        $suitable = count($selected) > 0 ? $selected : null;

        if ($request->boolean('remove_photo') && $room->photo && $room->photo !== 'default.jpg') {
            if (Storage::disk('public')->exists($room->photo)) {
                Storage::disk('public')->delete($room->photo);
            }
            $room->photo = 'default.jpg';
        }

        if ($request->hasFile('photo')) {
            if ($room->photo && $room->photo !== 'default.jpg' && Storage::disk('public')->exists($room->photo)) {
                Storage::disk('public')->delete($room->photo);
            }
            $room->photo = $request->file('photo')->store('rooms', 'public');
        }

        $room->name = $request->name;
        $room->type = $request->type;
        $room->description = $request->description;
        $room->suitable_training_types = $suitable;
        $room->save();

        return redirect()->back()->with('success', 'помещение обновлено.');
    }
}
