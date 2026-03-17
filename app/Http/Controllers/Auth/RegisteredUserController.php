<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Services\SignupTrialService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Rules\Recaptcha;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'g-recaptcha-response' => array_filter([config('recaptcha.secret_key') ? 'required' : null, new Recaptcha]),
        ]);

        [$user, $organization] = DB::transaction(function () use ($request) {
            $organization = Organization::create([
                'name' => $request->organization_name,
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $organization->users()->attach($user->id);

            return [$user, $organization];
        });

        try {
            app(SignupTrialService::class)->grantIfEligible($organization, 'register');
        } catch (\Throwable $e) {
            Log::warning('試用派發失敗', ['org' => $organization->id, 'error' => $e->getMessage()]);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect('/admin');
    }
}
