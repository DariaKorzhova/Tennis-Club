<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CancellationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'trainer_id',
        'status',
        'reason',
        'admin_comment',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
