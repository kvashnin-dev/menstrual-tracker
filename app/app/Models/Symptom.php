<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Symptom extends Model
{
    protected $fillable = ['key', 'name_ru', 'name_en', 'icon', 'color'];

    public function logs()
    {
        return $this->hasMany(SymptomLog::class);
    }
}
