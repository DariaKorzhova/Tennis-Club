<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $deleteName = 'Зал групповых занятий';

        $roomId = DB::table('rooms')->where('name', $deleteName)->value('id');
        if ($roomId) {
            $trainingIds = DB::table('room_training')->where('room_id', $roomId)->pluck('training_id');
            if ($trainingIds->isNotEmpty()) {
                DB::table('trainings')->whereIn('id', $trainingIds)->delete();
            }
            DB::table('rooms')->where('id', $roomId)->delete();
        }

        $courtTypes = json_encode(['individual', 'split', 'kids', 'group']);
        $yogaTypes = json_encode(['yoga']);
        $gymTypes = json_encode(['fitness']);
        $massageTypes = json_encode(['massage']);

        DB::table('rooms')->where('type', 'tennis_court')->update(['suitable_training_types' => $courtTypes]);
        DB::table('rooms')->where('type', 'yoga_hall')->update(['suitable_training_types' => $yogaTypes]);
        DB::table('rooms')->where('type', 'gym')->update(['suitable_training_types' => $gymTypes]);
        DB::table('rooms')->where('type', 'massage_room')->update(['suitable_training_types' => $massageTypes]);

        DB::table('trainings')->where('room_type', 'group_hall')->update(['room_type' => 'tennis_court']);
    }

    public function down(): void
    {
        DB::table('rooms')->where('type', 'tennis_court')->update(['suitable_training_types' => null]);
        DB::table('rooms')->where('type', 'yoga_hall')->update(['suitable_training_types' => null]);
        DB::table('rooms')->where('type', 'gym')->update(['suitable_training_types' => null]);
        DB::table('rooms')->where('type', 'massage_room')->update(['suitable_training_types' => null]);
    }
};
