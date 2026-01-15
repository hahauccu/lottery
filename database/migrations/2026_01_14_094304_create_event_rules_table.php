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
        Schema::create('event_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_event_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamps();

            $table->index(['lottery_event_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_rules');
    }
};
