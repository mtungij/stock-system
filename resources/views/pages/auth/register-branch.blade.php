<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Branch Information')" :description="__('Enter branch details')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.branch.store') }}" class="flex flex-col gap-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:input name="branch_name" :label="__('Branch name')" :value="old('branch_name', session('branch_name'))" type="text" required :placeholder="__('Branch name')" />
                <flux:input name="branch_phone" :label="__('Branch phone')" :value="old('branch_phone', session('branch_phone'))" type="text" required :placeholder="__('Phone number')" />
                <flux:input name="branch_address" :label="__('Branch address')" :value="old('branch_address', session('branch_address'))" type="text" required :placeholder="__('Address')" class="md:col-span-2" />
            </div>

            <div class="flex items-center gap-3">
                <flux:button type="button" variant="outline" class="w-full" onclick="window.location='{{ route('register.company') }}'">
                    {{ __('Back') }}
                </flux:button>
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
