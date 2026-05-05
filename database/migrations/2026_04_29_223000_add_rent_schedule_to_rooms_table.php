<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->json('rent_weekdays')->nullable()->after('suitable_training_types');
            $table->time('rent_start_time')->nullable()->after('rent_weekdays');
            $table->time('rent_end_time')->nullable()->after('rent_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn([
                'rent_weekdays',
                'rent_start_time',
                'rent_end_time',
            ]);
        });
    }
};
