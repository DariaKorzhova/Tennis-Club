<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();

        DB::table('room_training')->truncate();
        DB::table('trainings')->truncate();
        DB::table('rooms')->truncate();
        DB::table('corts')->truncate();
        DB::table('users')->truncate();

        Schema::enableForeignKeyConstraints();

        $this->call([
            RoomsTableSeeder::class,
            UsersTableSeeder::class,
            TrainingsSeeder::class,
        ]);
    }
}