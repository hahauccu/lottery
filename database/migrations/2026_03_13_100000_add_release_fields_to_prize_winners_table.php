<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prize_winners', function (Blueprint $table) {
            $table->timestamp('released_at')->nullable()->after('notified_at');
            $table->string('release_reason', 50)->nullable()->after('released_at');
            $table->foreignId('released_by_user_id')->nullable()->after('release_reason')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('replacement_for_winner_id')->nullable()->after('released_by_user_id')
                ->constrained('prize_winners')->nullOnDelete();

            $table->index(['prize_id', 'released_at']);
        });
    }

    public function down(): void
    {
        Schema::table('prize_winners', function (Blueprint $table) {
            $table->dropForeign(['released_by_user_id']);
            $table->dropForeign(['replacement_for_winner_id']);
            $table->dropIndex(['prize_id', 'released_at']);
            $table->dropColumn([
                'released_at',
                'release_reason',
                'released_by_user_id',
                'replacement_for_winner_id',
            ]);
        });
    }
};
