<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ECPay Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" the application is using for
    | ECPay. Set to "sandbox" for testing, "production" for live.
    |
    */
    'env' => env('ECPAY_ENV', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Merchant ID
    |--------------------------------------------------------------------------
    */
    'merchant_id' => env('ECPAY_MERCHANT_ID', '3002607'),

    /*
    |--------------------------------------------------------------------------
    | Hash Key & Hash IV
    |--------------------------------------------------------------------------
    */
    'hash_key' => env('ECPAY_HASH_KEY', 'pwFHCqoQZGmho4w6'),
    'hash_iv' => env('ECPAY_HASH_IV', 'EkRm7iFT261dpevs'),

    /*
    |--------------------------------------------------------------------------
    | API URLs
    |--------------------------------------------------------------------------
    */
    'url' => env('ECPAY_URL', 'https://payment-stage.ecpay.com.tw'),

    /*
    |--------------------------------------------------------------------------
    | Callback URLs
    |--------------------------------------------------------------------------
    |
    | return_url: Server to Server 背景通知
    | order_result_url: 用戶付款完成後導向的前台頁面
    |
    */
    'return_url' => env('ECPAY_RETURN_URL', env('APP_URL') . '/payment/ecpay/notify'),
    'order_result_url' => env('ECPAY_ORDER_RESULT_URL', env('APP_URL') . '/payment/ecpay/result'),
];
