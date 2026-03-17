<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\SignupTrialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use App\Rules\Recaptcha;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterAccountController extends Controller
{
    public function create(): View
    {
        return view('auth.register-account');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'g-recaptcha-response' => array_filter([config('recaptcha.secret_key') ? 'required' : null, new Recaptcha]),
        ]);

        $email = strtolower(trim($validated['email']));
        $organizationName = trim((string) ($validated['organization_name'] ?? ''));
        if ($organizationName === '') {
            $organizationName = $this->guessOrganizationName($email);
        }

        $password = Str::password(16);

        [$user, $organization] = DB::transaction(function () use ($email, $organizationName, $password) {
            $organization = Organization::create([
                'name' => $organizationName,
            ]);

            $user = User::create([
                'name' => $this->guessUserName($email),
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $organization->users()->attach($user->id);

            return [$user, $organization];
        });

        try {
            app(SignupTrialService::class)->grantIfEligible($organization, 'register-account');
        } catch (\Throwable $e) {
            Log::warning('試用派發失敗', ['org' => $organization->id, 'error' => $e->getMessage()]);
        }

        Password::sendResetLink(['email' => $user->email]);

        return redirect()
            ->route('register-account')
            ->with([
                'registered_email' => $user->email,
            ]);
    }

    private function guessUserName(string $email): string
    {
        $name = trim((string) Str::before($email, '@'));

        return $name !== '' ? $name : $email;
    }

    private function guessOrganizationName(string $email): string
    {
        $domain = trim((string) Str::after($email, '@'));
        $base = trim((string) Str::before($domain, '.'));

        if ($base !== '') {
            return Str::title($base);
        }

        return '新公司';
    }
}
