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
        Schema::table('employee_groups', function (Blueprint $table) {
            $table->string('system_key', 50)->nullable()->after('name');
            $table->foreignId('lottery_event_id')->nullable()->after('system_key')
                ->constrained()->cascadeOnDelete();
            $table->foreignId('prize_id')->nullable()->after('lottery_event_id')
                ->constrained()->cascadeOnDelete();

            $table->unique(
                ['organization_id', 'lottery_event_id', 'system_key', 'prize_id'],
                'employee_groups_system_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_groups', function (Blueprint $table) {
            $table->dropUnique('employee_groups_system_unique');
            $table->dropForeign(['prize_id']);
            $table->dropForeign(['lottery_event_id']);
            $table->dropColumn(['system_key', 'lottery_event_id', 'prize_id']);
        });
    }
};
