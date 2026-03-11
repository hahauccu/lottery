<?php

namespace App\Support;

class AccessCodeGenerator
{
    /**
     * 產生當日存取碼：HMAC-SHA256(brandCode + Ymd, APP_KEY) 取前 8 碼大寫英數
     */
    public static function generate(string $brandCode): string
    {
        $date = now()->format('Ymd');
        $hash = hash_hmac('sha256', $brandCode.$date, config('app.key'));

        // 取前 8 碼並轉大寫
        return strtoupper(substr($hash, 0, 8));
    }

    /**
     * 驗證存取碼是否正確
     */
    public static function verify(string $brandCode, string $code): bool
    {
        return hash_equals(self::generate($brandCode), strtoupper(trim($code)));
    }
}
