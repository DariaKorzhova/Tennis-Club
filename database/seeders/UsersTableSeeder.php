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

        User::create([
            'first_name' => 'Иван',
            'last_name' => 'Теннисный',
            'email' => 'tennis@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1989-04-12',
            'role' => 'trainer',
            'specialization' => 'tennis_trainer',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Анна',
            'last_name' => 'Фитнесова',
            'email' => 'fitness@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1996-06-25',
            'role' => 'trainer',
            'specialization' => 'fitness_trainer',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Мария',
            'last_name' => 'Йогина',
            'email' => 'yoga@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1992-11-03',
            'role' => 'trainer',
            'specialization' => 'yoga_trainer',
            'photo' => 'trainers/maria.jpg',
            'two_factor_enabled' => true,
        ]);

        User::create([
            'first_name' => 'Сергей',
            'last_name' => 'Массажистов',
            'email' => 'massage@example.com',
            'password' => Hash::make('password123'),
            'birth_date' => '1985-02-17',
            'role' => 'trainer',
            'specialization' => 'masseur',
            'photo' => null,
            'two_factor_enabled' => true,
        ]);

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

        $this->command->info('Добавлено тестовых пользователей');
    }
}