<?php

return [

    'title' => '重設密碼',

    'heading' => '忘記密碼？',

    'actions' => [

        'login' => [
            'label' => '返回登入頁面',
        ],

    ],

    'form' => [

        'email' => [
            'label' => '電子信箱',
        ],

        'actions' => [

            'request' => [
                'label' => '寄送重設連結',
            ],

        ],

    ],

    'notifications' => [

        'throttled' => [
            'title' => '嘗試次數過多',
            'body' => '請在 :seconds 秒後重試。',
        ],

    ],

];
