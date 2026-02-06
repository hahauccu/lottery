<?php

namespace App\Models;

use App\Services\SystemGroupService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LotteryEvent extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'brand_code',
        'default_bg_image_path',
        'current_prize_id',
        'is_lottery_open',
        'show_prizes_preview',
        'danmaku_enabled',
    ];

    protected static function booted(): void
    {
        static::creating(function (LotteryEvent $event) {
            if ($event->brand_code) {
                return;
            }

            do {
                $code = strtoupper(Str::random(6));
            } while (self::where('brand_code', $code)->exists());

            $event->brand_code = $code;
        });

        static::created(function (LotteryEvent $event) {
            app(SystemGroupService::class)->ensureEventSystemGroups($event);
        });
    }

    protected $casts = [
        'is_lottery_open' => 'boolean',
        'show_prizes_preview' => 'boolean',
        'danmaku_enabled' => 'boolean',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(Prize::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(EventRule::class);
    }

    public function currentPrize(): BelongsTo
    {
        return $this->belongsTo(Prize::class, 'current_prize_id');
    }
}
