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
        Schema::create('prize_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prize_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamps();

            $table->index(['prize_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prize_rules');
    }
};
