<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            // Убрали name — не нужен (можно по желанию вернуть)
            // $table->string('name')->nullable();

            // === Настройки менструального календаря ===
            $table->boolean('is_pregnant')->default(false);
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('average_cycle_length')->default(28)->comment('Средняя длина цикла')->nullable();
            $table->unsignedTinyInteger('average_period_length')->default(5)->comment('Средняя длительность месячных')->nullable();

            // Индексы
            $table->index('is_pregnant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
