<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCortsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('corts', function (Blueprint $table) {
            $table->id();
            $table->integer('number');
            $table->timestamps();
            $table->string('photo');
            $table->string('discription');
            $table->enum('season', ['open', 'close'])->default('open');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('corts');
    }
}
