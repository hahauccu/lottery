<?php

namespace App\Services;

use Illuminate\Support\Collection;

class EcpayLogReader
{
    /**
     * 讀取指定日期範圍的日誌
     */
    public function getLogs(?string $startDate = null, ?string $endDate = null, ?string $type = null): Collection
    {
        $logs = collect();
        $basePath = storage_path('logs');

        if (! is_dir($basePath)) {
            return $logs;
        }

        // 取得所有年份目錄
        $years = $this->getDirectories($basePath);

        foreach ($years as $year) {
            $yearPath = $basePath.'/'.$year;
            $months = $this->getDirectories($yearPath);

            foreach ($months as $month) {
                $monthPath = $yearPath.'/'.$month;
                $days = $this->getDirectories($monthPath);

                foreach ($days as $day) {
                    $dayPath = $monthPath.'/'.$day;
                    $hours = $this->getDirectories($dayPath);

                    foreach ($hours as $hour) {
                        $logFile = $dayPath.'/'.$hour.'/ecpay.log';

                        if (! file_exists($logFile)) {
                            continue;
                        }

                        // 檢查日期範圍
                        $logDate = "{$year}-{$month}-{$day}";
                        if ($startDate && $logDate < $startDate) {
                            continue;
                        }
                        if ($endDate && $logDate > $endDate) {
                            continue;
                        }

                        // 讀取日誌檔案
                        $entries = $this->parseLogFile($logFile, $type);
                        $logs = $logs->merge($entries);
                    }
                }
            }
        }

        // 按時間降序排序
        return $logs->sortByDesc('time')->values();
    }

    /**
     * 解析單一日誌檔案
     */
    protected function parseLogFile(string $filePath, ?string $type = null): Collection
    {
        $entries = collect();
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $entry = json_decode($line, true);

            if (! $entry) {
                continue;
            }

            // 篩選類型
            if ($type && ($entry['type'] ?? '') !== $type) {
                continue;
            }

            // 解析並加工資料
            $entries->push($this->formatEntry($entry));
        }

        return $entries;
    }

    /**
     * 格式化日誌條目
     */
    protected function formatEntry(array $entry): array
    {
        $data = $entry['data'] ?? [];
        $type = $entry['type'] ?? 'unknown';

        // 提取常用欄位
        $merchantTradeNo = $data['merchant_trade_no']
            ?? $data['MerchantTradeNo']
            ?? $data['params']['MerchantTradeNo']
            ?? null;

        $amount = $data['amount']
            ?? $data['TradeAmt']
            ?? $data['params']['TotalAmount']
            ?? null;

        // 判斷狀態
        $status = $this->determineStatus($type, $data);

        return [
            'time' => $entry['time'] ?? null,
            'type' => $type,
            'merchant_trade_no' => $merchantTradeNo,
            'amount' => $amount,
            'status' => $status,
            'data' => $data,
        ];
    }

    /**
     * 判斷日誌狀態
     */
    protected function determineStatus(string $type, array $data): string
    {
        if ($type === 'checkout') {
            return 'pending';
        }

        if ($type === 'notify' || $type === 'result') {
            $rtnCode = $data['RtnCode'] ?? null;
            if ($rtnCode === '1' || $rtnCode === 1) {
                return 'success';
            }
            if ($rtnCode !== null) {
                return 'failed';
            }
        }

        if ($type === 'notify_result') {
            return $data['success'] ?? false ? 'success' : 'failed';
        }

        return 'unknown';
    }

    /**
     * 取得目錄下的子目錄
     */
    protected function getDirectories(string $path): array
    {
        if (! is_dir($path)) {
            return [];
        }

        $items = scandir($path);
        $dirs = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (is_dir($path.'/'.$item)) {
                $dirs[] = $item;
            }
        }

        sort($dirs);

        return $dirs;
    }

    /**
     * 取得可用的日誌類型
     */
    public function getAvailableTypes(): array
    {
        return [
            'checkout' => '發起付款',
            'notify' => '背景通知',
            'notify_result' => '通知處理結果',
            'result' => '前台返回',
        ];
    }
}
