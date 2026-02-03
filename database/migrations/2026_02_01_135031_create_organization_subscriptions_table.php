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
        Schema::create('organization_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'expires_at'], 'org_sub_status_expires_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_subscriptions');
    }
};
