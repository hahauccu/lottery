<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_lottery_templates', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->string('title', 60);
            $table->string('category', 40)->nullable();
            $table->string('city', 40)->nullable();
            $table->string('district', 40)->nullable();
            $table->string('description', 160)->nullable();
            $table->json('options')->nullable();
            $table->string('animation_style', 60);
            $table->unsignedTinyInteger('draw_count')->default(1);
            $table->string('draw_mode', 30)->default('all_at_once');
            $table->boolean('is_public')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_indexable')->default(false);
            $table->unsignedInteger('uses_count')->default(0);
            $table->unsignedInteger('reports_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['is_public', 'is_featured']);
            $table->index(['category', 'is_public']);
            $table->index('last_used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_lottery_templates');
    }
};
