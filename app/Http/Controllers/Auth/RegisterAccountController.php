<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegisterAccountPasswordMail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
        ]);

        $email = strtolower(trim($validated['email']));
        $organizationName = trim((string) ($validated['organization_name'] ?? ''));
        if ($organizationName === '') {
            $organizationName = $this->guessOrganizationName($email);
        }

        $password = Str::password(16);

        $user = DB::transaction(function () use ($email, $organizationName, $password) {
            $organization = Organization::create([
                'name' => $organizationName,
            ]);

            $user = User::create([
                'name' => $this->guessUserName($email),
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $organization->users()->attach($user->id);

            return $user;
        });

        Mail::to($user->email)->queue(new RegisterAccountPasswordMail(
            email: $user->email,
            password: $password,
            organizationName: $organizationName,
            loginUrl: route('login')
        ));

        return redirect()
            ->route('login')
            ->with('status', '已建立帳號，登入密碼已寄送到你的信箱。');
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
