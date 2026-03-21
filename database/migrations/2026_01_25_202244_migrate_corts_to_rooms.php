<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Cort;

class MigrateCortsToRooms extends Migration
{
    public function up()
    {
        // Получаем все корты
        $corts = Cort::all();
        
        foreach ($corts as $cort) {
            DB::table('rooms')->insert([
                'name' => 'Корт №' . $cort->number,
                'type' => 'tennis_court',
                'description' => $cort->discription,
                'photo' => $cort->photo,
                'season' => $cort->season, // добавьте это поле в таблицу rooms
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        // После миграции можно удалить таблицу corts
        // Schema::dropIfExists('corts');
    }

    public function down()
    {
        // Откат миграции
        DB::table('rooms')->where('type', 'tennis_court')->delete();
    }
}