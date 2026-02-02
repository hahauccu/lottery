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
        Schema::table('prize_winners', function (Blueprint $table) {
            $table->string('claim_token', 64)->unique()->nullable()->after('won_at');
            $table->timestamp('claimed_at')->nullable()->after('claim_token');
            $table->timestamp('notified_at')->nullable()->after('claimed_at');
        });
    }

    public function down(): void
    {
        Schema::table('prize_winners', function (Blueprint $table) {
            $table->dropColumn(['claim_token', 'claimed_at', 'notified_at']);
        });
    }
};
