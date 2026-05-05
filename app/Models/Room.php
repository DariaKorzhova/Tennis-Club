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
        'photo',
        'suitable_training_types',
    ];

    protected $casts = [
        'suitable_training_types' => 'array',
    ];

    public function trainings()
    {
        return $this->belongsToMany(Training::class, 'room_training');
    }

    public function courtBookings()
    {
        return $this->hasMany(CourtBooking::class);
    }

    public static function getRoomTypes(): array
    {
        return [
            'tennis_court' => 'теннисный корт',
            'yoga_hall' => 'зал для йоги',
            'gym' => 'тренажерный зал',
            'massage_room' => 'массажный кабинет',
        ];
    }

    /**
     * Какие типы тренировок вообще допустимы для данного типа помещения (верхняя граница при настройке и валидации).
     */
    public static function allowedTrainingTypesForRoomType(?string $roomType): array
    {
        switch ($roomType) {
            case 'tennis_court':
                return ['individual', 'split', 'kids', 'group'];
            case 'yoga_hall':
                return ['yoga'];
            case 'gym':
                return ['fitness'];
            case 'massage_room':
                return ['massage'];
            default:
                return [];
        }
    }

    /**
     * Значение по умолчанию, если в БД не задан список suitable_training_types.
     */
    public static function defaultTrainingTypesForRoomType(?string $roomType): array
    {
        return self::allowedTrainingTypesForRoomType($roomType);
    }

    public function getEffectiveSuitableTrainingTypes(): array
    {
        $stored = $this->suitable_training_types;
        if (is_array($stored) && count($stored) > 0) {
            return array_values(array_unique(array_filter($stored, 'is_string')));
        }

        return self::defaultTrainingTypesForRoomType($this->type);
    }

    public static function acceptsTrainingType(self $room, string $trainingType): bool
    {
        return in_array($trainingType, $room->getEffectiveSuitableTrainingTypes(), true);
    }
}
