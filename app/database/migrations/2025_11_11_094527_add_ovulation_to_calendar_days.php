<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calendar_days', function (Blueprint $table) {
            $table->boolean('is_ovulation_predicted')->default(false)->after('is_period_predicted');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_days', function (Blueprint $table) {
            $table->dropColumn('is_ovulation_predicted');
        });
    }
};
