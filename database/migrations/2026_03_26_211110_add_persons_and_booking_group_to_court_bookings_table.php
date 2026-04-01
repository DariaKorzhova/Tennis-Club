<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPersonsAndBookingGroupToCourtBookingsTable extends Migration
{
    public function up()
    {
        Schema::table('court_bookings', function (Blueprint $table) {
            $table->unsignedInteger('persons')->default(1)->after('price');
            $table->string('booking_group')->nullable()->after('persons');
        });
    }

    public function down()
    {
        Schema::table('court_bookings', function (Blueprint $table) {
            $table->dropColumn(['persons', 'booking_group']);
        });
    }
}