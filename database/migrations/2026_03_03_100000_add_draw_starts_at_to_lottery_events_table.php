<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lottery_events', function (Blueprint $table) {
            $table->timestamp('draw_starts_at')->nullable()->after('prize_switched_at');
        });

        DB::statement('UPDATE lottery_events SET draw_starts_at = created_at WHERE draw_starts_at IS NULL');

        Schema::table('lottery_events', function (Blueprint $table) {
            $table->timestamp('draw_starts_at')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('lottery_events', function (Blueprint $table) {
            $table->dropColumn('draw_starts_at');
        });
    }
};
