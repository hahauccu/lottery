<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('day');
            $table->string('event', 30);
            $table->string('animation_style', 60);
            $table->string('entry', 10);
            $table->unsignedInteger('total')->default(0);
            $table->unsignedBigInteger('duration_total_ms')->default(0);
            $table->timestamps();
            $table->unique(['day', 'event', 'animation_style', 'entry']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_metrics');
    }
};
