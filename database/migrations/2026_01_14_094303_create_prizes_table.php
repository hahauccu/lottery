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
        Schema::create('prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('winners_count')->default(1);
            $table->string('draw_mode')->default('all_at_once');
            $table->string('bg_image_path')->nullable();
            $table->string('music_path')->nullable();
            $table->boolean('allow_repeat_within_prize')->default(false);
            $table->timestamps();

            $table->index(['lottery_event_id', 'draw_mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prizes');
    }
};
