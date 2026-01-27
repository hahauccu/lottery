<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lottery_analysis_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lottery_event_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('iterations');
            $table->string('status')->index();
            $table->unsignedTinyInteger('progress')->default(0);
            $table->json('result')->nullable();
            $table->string('error_message', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['lottery_event_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lottery_analysis_runs');
    }
};
