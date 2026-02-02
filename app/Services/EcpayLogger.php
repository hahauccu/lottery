<?php

namespace App\Services;

class EcpayLogger
{
    /**
     * 記錄綠界相關的日誌
     *
     * @param  string  $type  日誌類型：checkout, notify, result
     * @param  array  $data  日誌資料
     */
    public static function log(string $type, array $data): void
    {
        $path = storage_path(sprintf(
            'logs/%s/%s/%s/%s/ecpay.log',
            date('Y'),
            date('m'),
            date('d'),
            date('H')
        ));

        // 確保目錄存在
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $log = [
            'time' => now()->toIso8601String(),
            'type' => $type,
            'data' => $data,
        ];

        file_put_contents($path, json_encode($log, JSON_UNESCAPED_UNICODE)."\n", FILE_APPEND);
    }
}
