<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->date('birth_date');

            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            $table->string('photo')->nullable();

            $table->enum('role', ['user', 'admin', 'trainer'])->default('user');

            $table->enum('specialization', [
                'none',
                'tennis_trainer',
                'fitness_trainer',
                'yoga_trainer',
                'masseur'
            ])->default('none')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
}