<?php

namespace App\Support;

class DemoPreviews
{
    public static function forStyle(string $key): ?array
    {
        if (! in_array($key, ['battle_top', 'marble_race'], true)) {
            return null;
        }
        $images = [];
        foreach (['setup', 'action', 'result', 'mobile'] as $stage) {
            $path = 'images/demos/'.$key.'/'.$stage.'.jpg';
            if (! is_file(public_path($path))) {
                return null;
            }
            [$width, $height] = getimagesize(public_path($path));
            $images[$stage] = ['url' => url($path), 'width' => $width, 'height' => $height];
        }

        return $images;
    }
}
