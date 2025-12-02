<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Symptom extends Model
{
    use HasFactory;
    protected $fillable = ['key', 'name_ru', 'name_en', 'icon', 'color'];

    // Больше не нужен logs() — мы теперь храним symptom_key как строку
    // УДАЛИ метод logs(), если он есть
}
