<?php

namespace App\Models;

use App\Support\AnimationStyles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemoLotteryTemplate extends Model
{
    protected $fillable = [
        'token',
        'title',
        'category',
        'city',
        'district',
        'description',
        'options',
        'animation_style',
        'draw_count',
        'draw_mode',
        'is_public',
        'is_featured',
        'is_indexable',
        'uses_count',
        'reports_count',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'options' => 'array',
        'draw_count' => 'integer',
        'is_public' => 'boolean',
        'is_featured' => 'boolean',
        'is_indexable' => 'boolean',
        'uses_count' => 'integer',
        'reports_count' => 'integer',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function reports(): HasMany
    {
        return $this->hasMany(DemoLotteryTemplateReport::class);
    }

    public function publicReports(): HasMany
    {
        return $this->reports()
            ->where('is_public', true)
            ->where('is_hidden', false);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('is_public', true)
            ->where(fn (Builder $query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()));
    }

    public function styleLabel(): string
    {
        return AnimationStyles::labels()[$this->animation_style] ?? '抽獎動畫';
    }

    public function styleSlug(): string
    {
        return array_column(AnimationStyles::all(), 'slug', 'key')[$this->animation_style] ?? 'lotto-air';
    }

    public function optionsCount(): int
    {
        return count($this->options ?? []);
    }
}
