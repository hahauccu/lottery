<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_lottery_template_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demo_lottery_template_id')->constrained()->cascadeOnDelete();
            $table->string('result_text', 120);
            $table->string('author_name', 40)->nullable();
            $table->string('comment', 120)->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('is_hidden')->default(false);
            $table->string('ip_hash', 80)->nullable();
            $table->timestamps();

            $table->index(['demo_lottery_template_id', 'is_public', 'is_hidden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_lottery_template_reports');
    }
};
