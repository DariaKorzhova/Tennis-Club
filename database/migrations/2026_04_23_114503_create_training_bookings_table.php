<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrainingBookingsTable extends Migration
{
    public function up()
    {
        Schema::create('training_bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('training_id')->constrained('trainings')->cascadeOnDelete();

            // кто управляет записью
            $table->foreignId('account_user_id')->constrained('users')->cascadeOnDelete();

            // кто реально идёт на тренировку
            $table->enum('bookable_type', ['user', 'child']);
            $table->unsignedBigInteger('bookable_id');

            $table->foreignId('subscription_id')->nullable()->constrained('user_subscriptions')->nullOnDelete();

            $table->string('status')->default('active'); // active / cancelled
            $table->integer('price')->default(0);

            $table->timestamps();

            $table->unique(['training_id', 'bookable_type', 'bookable_id'], 'training_bookings_unique_person');
            $table->index(['account_user_id', 'bookable_type', 'bookable_id'], 'training_bookings_person_lookup');
        });
    }

    public function down()
    {
        Schema::dropIfExists('training_bookings');
    }
}