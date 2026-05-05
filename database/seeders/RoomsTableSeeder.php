<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomsTableSeeder extends Seeder
{
    public function run()
    {
        $courtTypes = ['individual', 'split', 'kids', 'group'];
        $yogaTypes = ['yoga'];
        $fitnessTypes = ['fitness'];
        $massageTypes = ['massage'];

        $rooms = [
            [
                'name' => 'Корт №1',
                'type' => 'tennis_court',
                'description' => 'Открытый теннисный корт с современным покрытием',
                'photo' => 'court1.jpg',
                'suitable_training_types' => $courtTypes,
            ],
            [
                'name' => 'Корт №2',
                'type' => 'tennis_court',
                'description' => 'Открытый теннисный корт для профессиональных игр',
                'photo' => 'court2.jpg',
                'suitable_training_types' => $courtTypes,
            ],
            [
                'name' => 'Корт №3',
                'type' => 'tennis_court',
                'description' => 'Закрытый теннисный корт для игры в любую погоду',
                'photo' => 'court3.jpg',
                'season' => 'close',
                'suitable_training_types' => $courtTypes,
            ],
            [
                'name' => 'Корт №4',
                'type' => 'tennis_court',
                'description' => 'Закрытый корт с системой кондиционирования',
                'photo' => 'court4.jpg',
                'suitable_training_types' => $courtTypes,
            ],

            [
                'name' => 'Зал для йоги №1',
                'type' => 'yoga_hall',
                'description' => 'Просторный зал для групповых занятий йогой',
                'photo' => 'yoga_hall1.jpg',
                'suitable_training_types' => $yogaTypes,
            ],
            [
                'name' => 'Зал для йоги №2',
                'type' => 'yoga_hall',
                'description' => 'Уютный зал для индивидуальных занятий йогой',
                'photo' => 'yoga_hall2.jpg',
                'suitable_training_types' => $yogaTypes,
            ],

            [
                'name' => 'Тренажерный зал',
                'type' => 'gym',
                'description' => 'Современный тренажерный зал с кардио-зоной',
                'photo' => 'gym1.jpg',
                'suitable_training_types' => $fitnessTypes,
            ],

            [
                'name' => 'Массажный кабинет №1',
                'type' => 'massage_room',
                'description' => 'Уютный кабинет для расслабляющего массажа',
                'photo' => 'massage1.jpg',
                'suitable_training_types' => $massageTypes,
            ],
            [
                'name' => 'Массажный кабинет №2',
                'type' => 'massage_room',
                'description' => 'Кабинет для лечебного и спортивного массажа',
                'photo' => 'massage2.jpg',
                'suitable_training_types' => $massageTypes,
            ],
        ];

        foreach ($rooms as $room) {
            Room::create($room);
        }

        $this->command->info('Добавлено ' . count($rooms) . ' помещений');
    }
}
