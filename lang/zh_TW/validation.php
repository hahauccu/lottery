<?php

return [

    'accepted' => ':attribute 必須被接受。',
    'between' => [
        'numeric' => ':attribute 必須介於 :min 到 :max 之間。',
        'string' => ':attribute 必須介於 :min 到 :max 個字元之間。',
    ],
    'confirmed' => ':attribute 確認不一致。',
    'email' => ':attribute 必須是有效的電子郵件地址。',
    'exists' => '所選的 :attribute 無效。',
    'lowercase' => ':attribute 必須為小寫。',
    'max' => [
        'numeric' => ':attribute 不可大於 :max。',
        'string' => ':attribute 不可超過 :max 個字元。',
    ],
    'min' => [
        'numeric' => ':attribute 不可小於 :min。',
        'string' => ':attribute 不可少於 :min 個字元。',
    ],
    'required' => ':attribute 為必填欄位。',
    'string' => ':attribute 必須為字串。',
    'unique' => '此 :attribute 已被使用。',

    'attributes' => [
        'email' => 'Email',
        'password' => '密碼',
        'password_confirmation' => '確認密碼',
        'name' => '名稱',
        'organization_name' => '公司名稱',
    ],

];
