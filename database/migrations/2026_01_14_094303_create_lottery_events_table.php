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
        Schema::create('lottery_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('brand_code')->unique();
            $table->string('default_bg_image_path')->nullable();
            $table->unsignedBigInteger('current_prize_id')->nullable();
            $table->boolean('is_lottery_open')->default(false);
            $table->timestamps();

            $table->index('current_prize_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lottery_events');
    }
};
