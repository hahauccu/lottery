<?php

namespace App\Filament\Pages\Auth;

use App\Rules\Recaptcha;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public ?string $recaptchaToken = null;

    public function authenticate(): ?LoginResponse
    {
        $secret = config('recaptcha.secret_key');

        if (! empty($secret)) {
            $validator = Validator::make(
                ['g-recaptcha-response' => $this->recaptchaToken],
                ['g-recaptcha-response' => ['required', new Recaptcha]],
                ['g-recaptcha-response.required' => '請完成 reCAPTCHA 驗證。']
            );

            if ($validator->fails()) {
                $this->recaptchaToken = null;
                $this->dispatch('recaptcha-reset');

                throw ValidationException::withMessages([
                    'data.email' => $validator->errors()->first('g-recaptcha-response'),
                ]);
            }
        }

        try {
            return parent::authenticate();
        } catch (ValidationException $e) {
            // 登入失敗（密碼錯等），token 已被 Google 消耗，需重置 widget
            $this->recaptchaToken = null;
            $this->dispatch('recaptcha-reset');

            throw $e;
        }
    }
}
