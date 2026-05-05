<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Models\UserSubscription;
use App\Models\Training;
use App\Models\CourtBooking;
use App\Models\Child;
use Illuminate\Support\Facades\Crypt;

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
        'saved_card_number',
        'saved_card_expiry',
    ];

    protected $casts = [
        'email_verified_at'       => 'datetime',
        'birth_date'              => 'date',
        'two_factor_expires_at'   => 'datetime',
        'two_factor_enabled'      => 'boolean',
    ];

    public function getFullNameAttribute()
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getRoleNameAttribute()
    {
        $roles = [
            'user'    => 'пользователь',
            'admin'   => 'администратор',
            'trainer' => 'тренер',
        ];

        return $roles[$this->role] ?? $this->role;
    }

    public function getSpecializationNameAttribute()
    {
        $specializations = [
            'none'             => 'нет',
            'tennis_trainer'   => 'тренер по теннису',
            'fitness_trainer'  => 'тренер по фитнесу',
            'yoga_trainer'     => 'тренер по йоге',
            'masseur'          => 'массажист',
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

    public function courtBookings()
    {
        return $this->hasMany(CourtBooking::class, 'user_id');
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'user_id');
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class, 'user_id')
            ->where('status', 'active')
            ->latestOfMany();
    }

    public function allChildren()
    {
        return $this->hasMany(Child::class, 'user_id');
    }

    public function children()
    {
        return $this->hasMany(Child::class, 'user_id')
            ->where('is_active', true);
    }

    /**
     * Данные сохранённой карты для автозаполнения формы оплаты (без CVV).
     */
    public function savedCardForPaymentModal(): ?array
    {
        if (empty($this->saved_card_number) || empty($this->saved_card_expiry)) {
            return null;
        }

        try {
            $digits = Crypt::decryptString($this->saved_card_number);
            $expiry = Crypt::decryptString($this->saved_card_expiry);
        } catch (\Throwable $e) {
            return null;
        }

        if (strlen($digits) < 13) {
            return null;
        }

        $d = preg_replace('/\D/', '', $digits);

        return [
            'number' => trim(implode(' ', str_split($d, 4))),
            'expiry' => $expiry,
        ];
    }
}