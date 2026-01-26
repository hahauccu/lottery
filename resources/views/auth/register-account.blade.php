<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if (session('registered_password'))
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">帳號已建立，請立即保存以下資訊：</p>
            <div class="mt-3 rounded-md bg-white p-3 text-gray-700">
                <p>公司名稱：<strong>{{ session('registered_org') }}</strong></p>
                <p>登入帳號：<strong>{{ session('registered_email') }}</strong></p>
                <p>登入密碼：<strong>{{ session('registered_password') }}</strong></p>
            </div>
            <p class="mt-3">此密碼僅顯示一次，請登入後立即修改。</p>
            <a class="mt-3 inline-flex items-center text-sm font-semibold text-amber-700 hover:text-amber-800" href="{{ session('login_url') }}">
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
