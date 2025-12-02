<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_symptom_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('symptom_key', 50); // cramps, sex, headache, mood_good и т.д.
            $table->text('note')->nullable(); // заметка на день (если нужно)
            $table->timestamps();

            // Быстрый поиск по пользователю и дате
            $table->index(['user_id', 'date']);

            // Один и тот же симптом в один день — только один раз
            $table->unique(['user_id', 'date', 'symptom_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_symptom_logs');
    }
};
