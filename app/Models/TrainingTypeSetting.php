<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingTypeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'price',
        'persons_min',
        'persons_max',
        'persons_fixed',
        'trainer_ids',
        'weekdays',
        'time_start',
        'time_end',
    ];

    protected $casts = [
        'trainer_ids' => 'array',
        'weekdays' => 'array',
    ];
}
