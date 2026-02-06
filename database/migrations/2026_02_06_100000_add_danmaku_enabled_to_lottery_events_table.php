<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lottery_events', function (Blueprint $table) {
            $table->boolean('danmaku_enabled')->default(false)->after('show_prizes_preview');
        });
    }

    public function down(): void
    {
        Schema::table('lottery_events', function (Blueprint $table) {
            $table->dropColumn('danmaku_enabled');
        });
    }
};
