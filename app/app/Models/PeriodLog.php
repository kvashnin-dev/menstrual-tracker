<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodLog extends Model
{
    use HasFactory;
// app/Models/PeriodLog.php
    protected $fillable = ['user_id', 'date', 'note'];
    protected $dates = ['date'];
    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
