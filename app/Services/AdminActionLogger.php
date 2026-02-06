<?php

namespace App\Services;

class AdminActionLogger
{
    public static function log(array $data): void
    {
        $path = storage_path(sprintf(
            'logs/%s/%s/%s/%s/admin.log',
            date('Y'),
            date('m'),
            date('d'),
            date('H')
        ));

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $log = [
            'time' => now()->toIso8601String(),
            'type' => 'admin_action',
            'data' => $data,
        ];

        file_put_contents($path, json_encode($log, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
    }
}
