<?php

namespace App\Support;

class DataMasker
{
    /**
     * 遮罩 email：保留第一個字元 + @ 後的域名，中間用 ***
     *
     * e.g. "winner@example.com" → "w***@example.com"
     */
    public static function maskEmail(?string $email): ?string
    {
        if (! $email || ! str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);

        if (strlen($local) <= 1) {
            return '*@'.$domain;
        }

        return $local[0].'***@'.$domain;
    }

    /**
     * 遮罩電話：保留前 2 碼 + 後 3 碼，中間用 ***
     * e.g. "0912345678" → "09***678"
     */
    public static function maskPhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) <= 5) {
            return str_repeat('*', strlen($digits));
        }

        return substr($digits, 0, 2).'***'.substr($digits, -3);
    }
}
