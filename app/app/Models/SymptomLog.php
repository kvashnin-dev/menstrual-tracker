<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class SymptomLog extends Pivot
{
    protected $table = 'symptom_logs';
    public $incrementing = true;

    public function calendarDay()
    {
        return $this->belongsTo(CalendarDay::class);
    }

    public function symptom()
    {
        return $this->belongsTo(Symptom::class);
    }
}
