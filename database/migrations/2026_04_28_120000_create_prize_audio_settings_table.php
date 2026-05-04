<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prize_audio_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prize_id')->constrained()->cascadeOnDelete();
            $table->string('sound_key');
            $table->string('mode')->default('default');
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->unique(['prize_id', 'sound_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prize_audio_settings');
    }
};
