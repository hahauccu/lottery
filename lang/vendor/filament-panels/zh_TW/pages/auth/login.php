<?php

return [

    'title' => '登入',

    'heading' => '登入帳號',

    'actions' => [

        'register' => [
            'before' => '或',
            'label' => '註冊帳號',
        ],

        'request_password_reset' => [
            'label' => '忘記密碼？',
        ],

    ],

    'form' => [

        'email' => [
            'label' => '電子信箱',
        ],

        'password' => [
            'label' => '密碼',
        ],

        'remember' => [
            'label' => '記住我',
        ],

        'actions' => [

            'authenticate' => [
                'label' => '登入',
            ],

        ],

    ],

    'messages' => [

        'failed' => '帳號或密碼錯誤。',

    ],

    'notifications' => [

        'throttled' => [
            'title' => '嘗試登入次數過多',
            'body' => '請在 :seconds 秒後重試。',
        ],

    ],

];
