<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_symptom_logs', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'date', 'symptom_key']);
            $table->dropColumn('note');
        });
    }

    public function down(): void
    {
        Schema::table('user_symptom_logs', function (Blueprint $table) {
            $table->text('note')->nullable();
            $table->unique(['user_id', 'date', 'symptom_key'], 'user_symptom_logs_user_id_date_symptom_key_unique');
        });
    }
};
