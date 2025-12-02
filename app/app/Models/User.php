<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'is_pregnant',
        'due_date',
        'average_cycle_length',
        'average_period_length',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_pregnant' => 'boolean',
        'due_date' => 'date:Y-m-d',
        'average_cycle_length' => 'integer', // ✔ правильное имя
        'average_period_length' => 'integer',
    ];

    // === НОВЫЕ СВЯЗИ ===
    public function periodLogs()
    {
        return $this->hasMany(PeriodLog::class);
    }

    public function symptomLogs()
    {
        return $this->hasMany(UserSymptomLog::class);
    }
}
