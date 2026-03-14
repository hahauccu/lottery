<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('max_prizes_per_event')->nullable()->after('max_employees');
        });

        DB::table('subscription_plans')->insert([
            'name' => '免費版',
            'code' => 'free',
            'max_employees' => 50,
            'max_prizes_per_event' => 4,
            'price' => 0,
            'duration_days' => 36500,
            'description' => '適合 50 人以下的小型組織，每場活動最多 4 個獎項',
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('subscription_plans')->where('code', 'free')->delete();

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('max_prizes_per_event');
        });
    }
};
