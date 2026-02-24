<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('registered_email'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-900">
            <p class="font-semibold">帳號已建立！</p>
            <p class="mt-2">我們已寄送密碼設定連結到 <strong>{{ session('registered_email') }}</strong>，請前往信箱收信並設定您的登入密碼。</p>
            <p class="mt-2 text-green-700">若未收到信件，請檢查垃圾信匣。</p>
            <a class="mt-3 inline-flex items-center text-sm font-semibold text-green-700 hover:text-green-800" href="{{ route('login') }}">
                前往登入
            </a>
        </div>
    @endif

    <form method="POST" action="{{ route('register-account.store') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Organization Name -->
        <div class="mt-4">
            <x-input-label for="organization_name" :value="__('Organization Name (Optional)')" />
            <x-text-input id="organization_name" class="block mt-1 w-full" type="text" name="organization_name" :value="old('organization_name')" autocomplete="organization" />
            <x-input-error :messages="$errors->get('organization_name')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-6">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
