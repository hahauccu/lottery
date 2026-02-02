<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\SubscriptionPlan;

class EcpayService
{
    /**
     * 建立付款單，回傳綠界表單 HTML
     */
    public function createPayment(Organization $organization, SubscriptionPlan $plan): array
    {
        // 建立唯一的訂單編號（最長 20 碼）
        $merchantTradeNo = 'LT'.date('YmdHis').str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        // 建立 Payment 記錄
        $payment = Payment::create([
            'organization_id' => $organization->id,
            'subscription_plan_id' => $plan->id,
            'merchant_trade_no' => $merchantTradeNo,
            'amount' => $plan->price,
            'status' => 'pending',
        ]);

        // 準備綠界參數
        $params = [
            'MerchantID' => config('ecpay.merchant_id'),
            'MerchantTradeNo' => $merchantTradeNo,
            'MerchantTradeDate' => date('Y/m/d H:i:s'),
            'PaymentType' => 'aio',
            'TotalAmount' => $plan->price,
            'TradeDesc' => urlencode('訂閱方案購買'),
            'ItemName' => $plan->name.' - '.$plan->duration_days.'天',
            'ReturnURL' => config('ecpay.return_url'),
            'OrderResultURL' => config('ecpay.order_result_url'),
            'ChoosePayment' => 'ALL',
            'EncryptType' => 1,
            'NeedExtraPaidInfo' => 'N',
        ];

        // 產生檢查碼
        $params['CheckMacValue'] = $this->generateCheckMacValue($params);

        // 產生表單 HTML
        $formHtml = $this->generateFormHtml($params);

        return [
            'payment' => $payment,
            'form_html' => $formHtml,
            'params' => $params,
        ];
    }

    /**
     * 處理綠界回調
     */
    public function handleCallback(array $data): array
    {
        $result = [
            'success' => false,
            'payment' => null,
            'message' => '',
        ];

        // 驗證檢查碼
        if (! $this->verifyCheckMacValue($data)) {
            $result['message'] = 'CheckMacValue 驗證失敗';

            return $result;
        }

        // 查詢付款記錄
        $merchantTradeNo = $data['MerchantTradeNo'] ?? '';
        $payment = Payment::where('merchant_trade_no', $merchantTradeNo)->first();

        if (! $payment) {
            $result['message'] = '找不到對應的付款記錄';

            return $result;
        }

        $result['payment'] = $payment;

        // 檢查是否已處理過（冪等性）
        if ($payment->isPaid()) {
            $result['success'] = true;
            $result['message'] = '付款已處理';

            return $result;
        }

        // 檢查付款結果
        $rtnCode = $data['RtnCode'] ?? '';

        if ($rtnCode === '1') {
            // 付款成功
            $payment->markAsPaid($data);

            // 啟用訂閱
            $subscriptionService = app(SubscriptionService::class);
            $subscriptionService->purchaseSubscription(
                $payment->organization,
                $payment->plan,
                '線上付款購買 - 訂單編號: '.$merchantTradeNo
            );

            $result['success'] = true;
            $result['message'] = '付款成功，訂閱已啟用';
        } else {
            // 付款失敗
            $payment->markAsFailed($data);
            $result['message'] = '付款失敗：'.($data['RtnMsg'] ?? '未知錯誤');
        }

        return $result;
    }

    /**
     * 驗證綠界檢查碼
     */
    public function verifyCheckMacValue(array $data): bool
    {
        if (! isset($data['CheckMacValue'])) {
            return false;
        }

        $checkMacValue = $data['CheckMacValue'];
        unset($data['CheckMacValue']);

        $generatedCheckMacValue = $this->generateCheckMacValue($data);

        return strtoupper($checkMacValue) === strtoupper($generatedCheckMacValue);
    }

    /**
     * 產生綠界檢查碼（SHA256）
     */
    public function generateCheckMacValue(array $data): string
    {
        // 移除 CheckMacValue（如果存在）
        unset($data['CheckMacValue']);

        // 按照 key 排序
        ksort($data);

        // 組合字串
        $checkStr = 'HashKey='.config('ecpay.hash_key');
        foreach ($data as $key => $value) {
            $checkStr .= '&'.$key.'='.$value;
        }
        $checkStr .= '&HashIV='.config('ecpay.hash_iv');

        // URL encode
        $checkStr = urlencode($checkStr);

        // 轉小寫
        $checkStr = strtolower($checkStr);

        // 處理特殊字元（依照綠界規範）
        $checkStr = str_replace('%2d', '-', $checkStr);
        $checkStr = str_replace('%5f', '_', $checkStr);
        $checkStr = str_replace('%2e', '.', $checkStr);
        $checkStr = str_replace('%21', '!', $checkStr);
        $checkStr = str_replace('%2a', '*', $checkStr);
        $checkStr = str_replace('%28', '(', $checkStr);
        $checkStr = str_replace('%29', ')', $checkStr);

        // SHA256 加密並轉大寫
        return strtoupper(hash('sha256', $checkStr));
    }

    /**
     * 產生自動提交的表單 HTML
     */
    private function generateFormHtml(array $params): string
    {
        $url = config('ecpay.url').'/Cashier/AioCheckOut/V5';

        $html = '<form id="ecpay-form" method="post" action="'.$url.'">';
        foreach ($params as $key => $value) {
            $html .= '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($value).'">';
        }
        $html .= '</form>';

        return $html;
    }
}
