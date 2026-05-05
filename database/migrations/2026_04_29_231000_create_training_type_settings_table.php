<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_type_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->integer('price')->default(1000);
            $table->unsignedSmallInteger('persons_min')->default(1);
            $table->unsignedSmallInteger('persons_max')->default(20);
            $table->unsignedSmallInteger('persons_fixed')->nullable();
            $table->json('trainer_ids')->nullable();
            $table->json('weekdays')->nullable();
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_type_settings');
    }
};
