<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarDay extends Model
{
    protected $fillable = ['date', 'is_period_predicted', 'is_period_confirmed', 'note'];

    protected $casts = [
        'date' => 'date',
        'is_period_predicted' => 'boolean',
        'is_period_confirmed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function symptoms()
    {
        return $this->belongsToMany(
            Symptom::class,
            'symptom_logs',      // ← твоя таблица
            'calendar_day_id',   // ← FK в symptom_logs
            'symptom_id'         // ← FK в symptom_logs
        );
    }

    public function symptomLogs()
    {
        return $this->hasMany(SymptomLog::class);
    }
}
