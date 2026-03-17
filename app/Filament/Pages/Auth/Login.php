<?php

namespace App\Filament\Pages\Auth;

use App\Rules\Recaptcha;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $secret = config('recaptcha.secret_key');

        if (! empty($secret)) {
            $recaptchaResponse = request()->input('g-recaptcha-response');

            $validator = Validator::make(
                ['g-recaptcha-response' => $recaptchaResponse],
                ['g-recaptcha-response' => ['required', new Recaptcha]],
                ['g-recaptcha-response.required' => '請完成 reCAPTCHA 驗證。']
            );

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'data.email' => $validator->errors()->first('g-recaptcha-response'),
                ]);
            }
        }

        return parent::authenticate();
    }
}
