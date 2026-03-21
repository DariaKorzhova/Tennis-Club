<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Auth\Passwords\CanResetPassword;

class User extends Authenticatable implements CanResetPasswordContract
{
    use HasApiTokens, HasFactory, Notifiable, CanResetPassword;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'birth_date',
        'password',
        'role',
        'photo',
        'specialization',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_code',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
        'two_factor_expires_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
    ];

    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function getRoleNameAttribute()
    {
        $roles = [
            'user' => 'Пользователь',
            'admin' => 'Администратор',
            'trainer' => 'Тренер'
        ];

        return $roles[$this->role] ?? $this->role;
    }

    public function getSpecializationNameAttribute()
    {
        $specializations = [
            'none' => 'Нет',
            'tennis_trainer' => 'Тренер по теннису',
            'fitness_trainer' => 'Тренер по фитнесу',
            'yoga_trainer' => 'Тренер по йоге',
            'masseur' => 'Массажист'
        ];

        return $specializations[$this->specialization] ?? $this->specialization;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }

        return asset('storage/' . $this->photo);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isTrainer()
    {
        return $this->role === 'trainer';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    public function bookedTrainings()
    {
        return $this->belongsToMany(Training::class, 'training_user')
            ->withPivot(['status', 'price'])
            ->withTimestamps();
    }

    public function trainingsAsTrainer()
    {
        return $this->hasMany(Training::class, 'trainer_id');
    }
}