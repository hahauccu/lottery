<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prize extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Prize $prize) {
            if (filled($prize->sort_order)) {
                return;
            }

            $max = static::query()
                ->where('lottery_event_id', $prize->lottery_event_id)
                ->max('sort_order');

            $prize->sort_order = ((int) $max) + 1;
        });
    }

    public const DRAW_MODE_ALL_AT_ONCE = 'all_at_once';
    public const DRAW_MODE_ONE_BY_ONE = 'one_by_one';

    protected $fillable = [
        'lottery_event_id',
        'name',
        'winners_count',
        'draw_mode',
        'bg_image_path',
        'music_path',
        'allow_repeat_within_prize',
        'sort_order',
    ];

    protected $casts = [
        'allow_repeat_within_prize' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function lotteryEvent(): BelongsTo
    {
        return $this->belongsTo(LotteryEvent::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(PrizeRule::class);
    }

    public function winners(): HasMany
    {
        return $this->hasMany(PrizeWinner::class);
    }
}

