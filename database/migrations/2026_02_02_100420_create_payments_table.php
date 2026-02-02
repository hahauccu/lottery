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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->string('merchant_trade_no', 20)->unique();  // 綠界訂單編號
            $table->string('trade_no')->nullable();              // 綠界交易編號
            $table->integer('amount');                           // 金額
            $table->string('status')->default('pending');        // pending, paid, failed
            $table->string('payment_type')->nullable();          // 付款方式
            $table->timestamp('paid_at')->nullable();            // 付款時間
            $table->json('ecpay_response')->nullable();          // 綠界回傳資料
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index('merchant_trade_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
