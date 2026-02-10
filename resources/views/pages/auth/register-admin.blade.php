<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Admin Account')" :description="__('Create your admin account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:input name="name" :label="__('Admin name')" :value="old('name')" type="text" required autofocus autocomplete="name" :placeholder="__('Full name')" />
                <flux:input name="email" :label="__('Admin email')" :value="old('email')" type="email" required autocomplete="email" placeholder="admin@example.com" />
                <flux:input name="phone" :label="__('Admin phone')" :value="old('phone')" type="text" required autocomplete="tel" :placeholder="__('Phone number')" class="md:col-span-2" />
                <flux:input name="password" :label="__('Password')" type="password" required autocomplete="new-password" :placeholder="__('Password')" viewable />
                <flux:input name="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" :placeholder="__('Confirm password')" viewable />
            </div>

            <div class="flex items-center gap-3">
                <flux:button type="button" variant="outline" class="w-full" onclick="window.location='{{ route('register.branch') }}'">
                    {{ __('Back') }}
                </flux:button>
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-white">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
