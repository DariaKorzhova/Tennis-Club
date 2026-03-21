<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'season',
        'description',
        'photo'
    ];

    public function trainings()
    {
        return $this->belongsToMany(Training::class, 'room_training');
    }

    public static function getRoomTypes()
    {
        return [
            'tennis_court' => 'Теннисный корт',
            'yoga_hall' => 'Зал для йоги',
            'group_hall' => 'Зал групповых тренировок',
            'gym' => 'Тренажерный зал',
            'massage_room' => 'Массажный кабинет'
        ];
    }
}
