<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCancellationRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('cancellation_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('trainer_id');

            // pending / approved / rejected
            $table->string('status')->default('pending');

            $table->text('reason')->nullable();
            $table->text('admin_comment')->nullable();

            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
            $table->foreign('trainer_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cancellation_requests');
    }
}
