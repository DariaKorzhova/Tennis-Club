<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoomTrainingTable extends Migration
{
    public function up()
    {
        Schema::create('room_training', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->foreignId('training_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['room_id', 'training_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('room_training');
    }
}