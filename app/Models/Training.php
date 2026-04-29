<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'time',
        'date',
        'duration',
        'persons',
        'price',
        'type',
        'room_type',
        'trainer_id',
        'is_cancelled',
    ];

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'room_training');
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'training_user')
            ->withPivot(['status', 'price'])
            ->withTimestamps();
    }

    public function cancellationRequests()
    {
        return $this->hasMany(CancellationRequest::class);
    }

    public static function getRoomTypeByTrainingType($trainingType)
    {
        $mapping = [
            'individual' => 'tennis_court',
            'split' => 'tennis_court',
            'kids' => 'tennis_court',
            'group' => 'group_hall',
            'fitness' => 'gym',
            'yoga' => 'yoga_hall',
            'massage' => 'massage_room'
        ];

        return $mapping[$trainingType] ?? 'tennis_court';
    }
}
