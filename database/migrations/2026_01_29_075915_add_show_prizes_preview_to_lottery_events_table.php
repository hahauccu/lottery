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
        Schema::table('lottery_events', function (Blueprint $table) {
            $table->boolean('show_prizes_preview')->default(false)->after('is_lottery_open');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lottery_events', function (Blueprint $table) {
            $table->dropColumn('show_prizes_preview');
        });
    }
};
