<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSymptomLog extends Model
{
    use HasFactory;
    protected $table = 'user_symptom_logs';
    protected $fillable = ['user_id', 'date', 'symptom_key', 'note'];
    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
