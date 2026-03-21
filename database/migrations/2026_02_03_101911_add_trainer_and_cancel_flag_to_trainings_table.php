<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTrainerAndCancelFlagToTrainingsTable extends Migration
{
    public function up()
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->unsignedBigInteger('trainer_id')->nullable()->after('room_type');
            $table->boolean('is_cancelled')->default(false)->after('trainer_id');

            $table->foreign('trainer_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropForeign(['trainer_id']);
            $table->dropColumn(['trainer_id', 'is_cancelled']);
        });
    }
}
