<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lottery_events', function (Blueprint $table) {
            $table->boolean('is_prize_switching')->default(false)->after('current_prize_id');
            $table->timestamp('prize_switched_at')->nullable()->after('is_prize_switching');
        });
    }

    public function down(): void
    {
        Schema::table('lottery_events', function (Blueprint $table) {
            $table->dropColumn(['is_prize_switching', 'prize_switched_at']);
        });
    }
};
