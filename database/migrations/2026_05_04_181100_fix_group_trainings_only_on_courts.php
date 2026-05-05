<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class FixGroupTrainingsOnlyOnCourts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::transaction(function () {
            $courtIds = DB::table('rooms')
                ->where('type', 'tennis_court')
                ->orderBy('name')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if (empty($courtIds)) {
                return;
            }

            // На всякий случай фиксируем room_type у "group"
            DB::table('trainings')->where('type', 'group')->update(['room_type' => 'tennis_court']);

            $groupTrainingIds = DB::table('trainings')
                ->where('type', 'group')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            foreach ($groupTrainingIds as $trainingId) {
                $attachedRooms = DB::table('room_training')
                    ->join('rooms', 'rooms.id', '=', 'room_training.room_id')
                    ->where('room_training.training_id', $trainingId)
                    ->select(['rooms.id as id', 'rooms.type as type'])
                    ->get();

                $attachedCourtId = null;
                foreach ($attachedRooms as $r) {
                    if ($r->type === 'tennis_court') {
                        $attachedCourtId = (int) $r->id;
                        break;
                    }
                }

                if (!$attachedCourtId) {
                    // Если по ошибке привязано только к залам — привяжем к корту (первому по имени)
                    $attachedCourtId = $courtIds[0];
                }

                // Оставляем ровно один корт и убираем все остальные помещения
                DB::table('room_training')->where('training_id', $trainingId)->delete();
                DB::table('room_training')->insert([
                    'room_id' => $attachedCourtId,
                    'training_id' => $trainingId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // необратимо: мы правим некорректные данные
    }
}
