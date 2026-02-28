<?php

return [
    'signup_trial' => [
        'enabled' => env('SIGNUP_TRIAL_ENABLED', false),
        'start_at' => env('SIGNUP_TRIAL_START_AT'),
        'end_at' => env('SIGNUP_TRIAL_END_AT'),
        'plan_code' => env('SIGNUP_TRIAL_PLAN_CODE', 'lv1'),
        'duration_days' => (int) env('SIGNUP_TRIAL_DURATION_DAYS', 7),
        'timezone' => env('SIGNUP_TRIAL_TIMEZONE', 'Asia/Taipei'),
    ],
];
