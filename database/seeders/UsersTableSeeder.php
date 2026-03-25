<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'first_name' => 'Дарья',
            'last_name' => 'Коржова',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1994-08-18',
            'role' => 'admin',
            'specialization' => 'none',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        // Тренеры по теннису
        User::create([
            'first_name' => 'Иван',
            'last_name' => 'Теннисный',
            'email' => 'tennis1@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1989-04-12',
            'role' => 'trainer',
            'specialization' => 'tennis_trainer',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Алексей',
            'last_name' => 'Смирнов',
            'email' => 'tennis2@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1991-07-21',
            'role' => 'trainer',
            'specialization' => 'tennis_trainer',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Дмитрий',
            'last_name' => 'Орлов',
            'email' => 'tennis3@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1987-03-09',
            'role' => 'trainer',
            'specialization' => 'tennis_trainer',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Егор',
            'last_name' => 'Волков',
            'email' => 'tennis4@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1993-12-01',
            'role' => 'trainer',
            'specialization' => 'tennis_trainer',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Никита',
            'last_name' => 'Лебедев',
            'email' => 'tennis5@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1990-10-15',
            'role' => 'trainer',
            'specialization' => 'tennis_trainer',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        // Тренеры по фитнесу
        User::create([
            'first_name' => 'Анна',
            'last_name' => 'Фитнесова',
            'email' => 'fitness1@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1996-06-25',
            'role' => 'trainer',
            'specialization' => 'fitness_trainer',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Елена',
            'last_name' => 'Кузнецова',
            'email' => 'fitness2@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1994-02-14',
            'role' => 'trainer',
            'specialization' => 'fitness_trainer',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Ольга',
            'last_name' => 'Романова',
            'email' => 'fitness3@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1991-08-30',
            'role' => 'trainer',
            'specialization' => 'fitness_trainer',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        // Тренеры по йоге
        User::create([
            'first_name' => 'Мария',
            'last_name' => 'Йогина',
            'email' => 'yoga1@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1992-11-03',
            'role' => 'trainer',
            'specialization' => 'yoga_trainer',
            'photo' => 'trainers/maria.jpg',
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Светлана',
            'last_name' => 'Миронова',
            'email' => 'yoga2@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1988-05-19',
            'role' => 'trainer',
            'specialization' => 'yoga_trainer',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Полина',
            'last_name' => 'Белова',
            'email' => 'yoga3@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1995-09-08',
            'role' => 'trainer',
            'specialization' => 'yoga_trainer',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        // Массажисты
        User::create([
            'first_name' => 'Сергей',
            'last_name' => 'Массажистов',
            'email' => 'massage1@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1985-02-17',
            'role' => 'trainer',
            'specialization' => 'masseur',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Павел',
            'last_name' => 'Зайцев',
            'email' => 'massage2@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1987-07-11',
            'role' => 'trainer',
            'specialization' => 'masseur',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Андрей',
            'last_name' => 'Ковалёв',
            'email' => 'massage3@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1984-04-27',
            'role' => 'trainer',
            'specialization' => 'masseur',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        // Пользователи
        User::create([
            'first_name' => 'Пользователь',
            'last_name' => 'Первый',
            'email' => 'user1@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '2001-09-14',
            'role' => 'user',
            'specialization' => 'none',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Пользователь',
            'last_name' => 'Второй',
            'email' => 'user2@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1999-01-22',
            'role' => 'user',
            'specialization' => 'none',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Пользователь',
            'last_name' => 'Третий',
            'email' => 'user3@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '2000-06-10',
            'role' => 'user',
            'specialization' => 'none',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        $this->command->info('Добавлены тестовые пользователи и расширенный состав тренеров');
    }
}