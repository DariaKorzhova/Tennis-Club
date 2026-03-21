<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingUserTable extends Migration
{
    public function up()
    {
        Schema::create('training_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('user_id');

            // active / cancelled
            $table->string('status')->default('active');

            // фиксируем цену на момент записи (по 1 человеку)
            $table->integer('price')->default(0);

            $table->timestamps();

            $table->unique(['training_id', 'user_id']);

            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('training_user');
    }
}
