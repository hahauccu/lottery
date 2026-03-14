<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'code',
        'max_employees',
        'max_prizes_per_event',
        'price',
        'duration_days',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'max_employees' => 'integer',
        'max_prizes_per_event' => 'integer',
        'price' => 'integer',
        'duration_days' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(OrganizationSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public static function freePlan(): ?self
    {
        return static::where('code', 'free')->first();
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price).' 元';
    }
}
