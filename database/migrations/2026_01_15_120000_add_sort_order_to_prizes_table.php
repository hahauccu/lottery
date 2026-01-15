<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('allow_repeat_within_prize');
            $table->index(['lottery_event_id', 'sort_order']);
        });

        DB::table('prizes')->update([
            'sort_order' => DB::raw('id'),
        ]);
    }

    public function down(): void
    {
        Schema::table('prizes', function (Blueprint $table) {
            $table->dropIndex(['lottery_event_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
