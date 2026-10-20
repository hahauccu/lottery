<?php

namespace App\Models;

use App\Support\AnimationStyles;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedback';

    public const CATEGORIES = [
        'animation_stuck' => '動畫卡住',
        'display_issue' => '顯示異常',
        'suggestion' => '功能建議',
        'other' => '其他',
    ];

    public const STATUSES = ['pending' => '待處理', 'resolved' => '已處理'];

    protected $fillable = [
        'category', 'message', 'email', 'source_path', 'animation_style',
        'viewport_width', 'viewport_height', 'status', 'admin_notes', 'notified_at',
    ];

    protected function casts(): array
    {
        return ['notified_at' => 'datetime'];
    }

    public static function sourcePaths(): array
    {
        $paths = ['/', '/faq', '/demo/lottery', '/demo/lottery/templates'];
        foreach (AnimationStyles::all() as $style) {
            $paths[] = '/demo/lottery/'.$style['slug'];
            $paths[] = '/demo/lottery/'.$style['slug'].'/play';
        }

        return $paths;
    }
}
