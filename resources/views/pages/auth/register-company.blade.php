<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter company details to get started')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.company.store') }}" class="flex flex-col gap-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:input name="company_name" :label="__('Business name')" :value="old('company_name', session('company_name'))" type="text" required :placeholder="__('Company name')" />
                <flux:input name="company_email" :label="__('Business email')" :value="old('company_email', session('company_email'))" type="email" required placeholder="company@example.com" />
                <flux:input name="company_phone" :label="__('Business phone')" :value="old('company_phone', session('company_phone'))" type="text" required :placeholder="__('Phone number')" />
                <flux:input name="company_address" :label="__('Business address')" :value="old('company_address', session('company_address'))" type="text" required :placeholder="__('Address')" />
            </div>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full">
                    {{ __('Next') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-white">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
