<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'organization_id',
        'subscription_plan_id',
        'merchant_trade_no',
        'trade_no',
        'amount',
        'status',
        'payment_type',
        'paid_at',
        'ecpay_response',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'ecpay_response' => 'array',
        'amount' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsPaid(array $ecpayResponse): void
    {
        $this->update([
            'status' => 'paid',
            'trade_no' => $ecpayResponse['TradeNo'] ?? null,
            'payment_type' => $ecpayResponse['PaymentType'] ?? null,
            'paid_at' => now(),
            'ecpay_response' => $ecpayResponse,
        ]);
    }

    public function markAsFailed(array $ecpayResponse): void
    {
        $this->update([
            'status' => 'failed',
            'ecpay_response' => $ecpayResponse,
        ]);
    }
}
