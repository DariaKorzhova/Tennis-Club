<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Training;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrainingsSeeder extends Seeder
{
    public function run()
    {
        DB::table('room_training')->delete();
        Training::query()->delete();

        $start = Carbon::today()->startOfDay();

        $roomsByType = Room::all()->groupBy('type');
        $trainers = User::where('role', 'trainer')->get();

        if ($trainers->isEmpty()) {
            $this->command->warn('Тренеры не найдены. Сначала запусти UsersTableSeeder.');
            return;
        }

        $times = [];
        for ($h = 8; $h <= 21; $h++) {
            $times[] = sprintf('%02d:00:00', $h);
        }

        $busyRoom = [];
        $busyTrainer = [];

        $created = 0;

        for ($dayOffset = 0; $dayOffset < 28; $dayOffset++) {
            $date = $start->copy()->addDays($dayOffset)->format('Y-m-d');

            foreach ($times as $time) {
                $want = rand(2, 6);

                for ($i = 0; $i < $want; $i++) {
                    $type = $this->pickTypeWeighted();

                    $persons = $this->getPersonsByType($type);
                    $duration = $this->getDurationByType($type);
                    $price = $this->getPriceByType($type);

                    $roomType = Training::getRoomTypeByTrainingType($type);

                    $room = $this->pickFreeRoom($roomsByType, $roomType, $date, $time, $busyRoom);
                    if (!$room) {
                        continue;
                    }

                    $trainer = $this->pickFreeTrainer($trainers, $type, $date, $time, $busyTrainer);
                    if (!$trainer) {
                        continue;
                    }

                    $training = Training::create([
                        'time' => $time,
                        'date' => $date,
                        'duration' => $duration,
                        'persons' => $persons,
                        'price' => $price,
                        'type' => $type,
                        'room_type' => $roomType,
                        'trainer_id' => $trainer->id,
                    ]);

                    $training->rooms()->attach($room->id);

                    $busyRoom[$this->key($date, $time, $room->id)] = true;
                    $busyTrainer[$this->key($date, $time, $trainer->id)] = true;

                    $created++;
                }
            }
        }

        $this->command->info('Добавлено ' . $created . ' тренировок на 28 дней от сегодняшней даты');
    }

    private function key(string $date, string $time, int $id): string
    {
        return $date . '|' . $time . '|' . $id;
    }

    private function pickTypeWeighted(): string
    {
        $pool = [
            'individual', 'individual', 'individual',
            'split', 'split',
            'kids', 'kids',
            'group', 'group', 'group',
            'fitness', 'fitness',
            'yoga',
            'massage'
        ];

        return $pool[array_rand($pool)];
    }

    private function pickFreeRoom($roomsByType, string $roomType, string $date, string $time, array $busyRoom)
    {
        $rooms = $roomsByType[$roomType] ?? collect();

        if ($rooms->isEmpty()) {
            $rooms = Room::all();
        }

        $candidates = $rooms->shuffle();

        foreach ($candidates as $room) {
            $k = $this->key($date, $time, (int) $room->id);
            if (!isset($busyRoom[$k])) {
                return $room;
            }
        }

        return null;
    }

    private function pickFreeTrainer($trainers, string $type, string $date, string $time, array $busyTrainer)
    {
        if ($trainers->isEmpty()) {
            return null;
        }

        $needSpec = $this->getRequiredSpecialization($type);
        $candidates = $trainers;

        if ($needSpec !== null) {
            $filtered = $trainers->filter(function ($t) use ($needSpec) {
                return isset($t->specialization) && $t->specialization === $needSpec;
            });

            if ($filtered->isNotEmpty()) {
                $candidates = $filtered;
            }
        }

        $candidates = $candidates->shuffle();

        foreach ($candidates as $trainer) {
            $k = $this->key($date, $time, (int) $trainer->id);
            if (!isset($busyTrainer[$k])) {
                return $trainer;
            }
        }

        return null;
    }

    private function getRequiredSpecialization(string $type): ?string
    {
        switch ($type) {
            case 'massage':
                return 'masseur';
            case 'yoga':
                return 'yoga_trainer';
            case 'fitness':
                return 'fitness_trainer';
            case 'individual':
            case 'split':
            case 'kids':
            case 'group':
                return 'tennis_trainer';
            default:
                return null;
        }
    }

    private function getPersonsByType(string $type): int
    {
        switch ($type) {
            case 'individual':
            case 'kids':
            case 'massage':
                return 1;
            case 'split':
                return 2;
            case 'group':
                return rand(6, 14);
            case 'yoga':
            case 'fitness':
                return rand(6, 14);
            default:
                return rand(2, 10);
        }
    }

    private function getDurationByType(string $type): string
    {
        $map = [
            'individual' => '1 час',
            'kids' => '1 час',
            'split' => '1.5 часа',
            'group' => '2 часа',
            'fitness' => (rand(0, 1) ? '1 час' : '1.5 часа'),
            'yoga' => '1.5 часа',
            'massage' => (rand(0, 1) ? '1 час' : '1.5 часа'),
        ];

        return $map[$type] ?? '1 час';
    }

    private function getPriceByType(string $type): int
    {
        switch ($type) {
            case 'individual':
                return 3500;
            case 'split':
                return 7500;
            case 'kids':
                return 4000;
            case 'group':
                return 2500;
            case 'fitness':
                return 1750;
            case 'yoga':
                return 1500;
            case 'massage':
                return 3000;
            default:
                return 1000;
        }
    }
}