<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Create an account')" :description="__('Enter your details below to create your account')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <div class="flex items-center justify-between text-xs text-white">
            <span class="font-semibold text-white">1. Company</span>
            <span>2. Branch</span>
            <span>3. Admin</span>
        </div>

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6" id="register-stepper">
            @csrf

            <input type="hidden" name="step" id="current-step" value="1" />

            <div data-step="1" class="flex flex-col gap-6">
                <flux:input name="company_name" :label="__('Company name')" :value="old('company_name')" type="text" required :placeholder="__('Company name')" />
                <flux:input name="company_email" :label="__('Company email')" :value="old('company_email')" type="email" required placeholder="company@example.com" />
                <flux:input name="company_phone" :label="__('Company phone')" :value="old('company_phone')" type="text" required :placeholder="__('Phone number')" />
                <flux:input name="company_address" :label="__('Company address')" :value="old('company_address')" type="text" required :placeholder="__('Address')" />

                <div class="flex items-center justify-end">
                    <flux:button type="button" variant="primary" class="w-full" data-step-next>
                        {{ __('Next') }}
                    </flux:button>
                </div>
            </div>

            <div data-step="2" class="flex flex-col gap-6 hidden">
                <flux:input name="branch_name" :label="__('Branch name')" :value="old('branch_name')" type="text" required :placeholder="__('Branch name')" />
                <flux:input name="branch_phone" :label="__('Branch phone')" :value="old('branch_phone')" type="text" required :placeholder="__('Phone number')" />
                <flux:input name="branch_address" :label="__('Branch address')" :value="old('branch_address')" type="text" required :placeholder="__('Address')" />

                <div class="flex items-center gap-3">
                    <flux:button type="button" variant="outline" class="w-full" data-step-prev>
                        {{ __('Back') }}
                    </flux:button>
                    <flux:button type="button" variant="primary" class="w-full" data-step-next>
                        {{ __('Next') }}
                    </flux:button>
                </div>
            </div>

            <div data-step="3" class="flex flex-col gap-6 hidden">
                <flux:input name="name" :label="__('Admin name')" :value="old('name')" type="text" required autofocus autocomplete="name" :placeholder="__('Full name')" />
                <flux:input name="email" :label="__('Admin email')" :value="old('email')" type="email" required autocomplete="email" placeholder="admin@example.com" />
                <flux:input name="phone" :label="__('Admin phone')" :value="old('phone')" type="text" required autocomplete="tel" :placeholder="__('Phone number')" />
                <flux:input name="password" :label="__('Password')" type="password" required autocomplete="new-password" :placeholder="__('Password')" viewable />
                <flux:input name="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" :placeholder="__('Confirm password')" viewable />

                <div class="flex items-center gap-3">
                    <flux:button type="button" variant="outline" class="w-full" data-step-prev>
                        {{ __('Back') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                        {{ __('Create account') }}
                    </flux:button>
                </div>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-white">
            <span>{{ __('Already have an account?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
        </div>
    </div>

    <script>
        (() => {
            const form = document.getElementById('register-stepper');
            if (!form) return;

            const steps = Array.from(form.querySelectorAll('[data-step]'));
            const input = document.getElementById('current-step');

            const showStep = (n) => {
                steps.forEach((el) => el.classList.toggle('hidden', el.dataset.step !== String(n)));
                if (input) input.value = String(n);
            };

            const validateStep = (n) => {
                const stepEl = steps.find((el) => el.dataset.step === String(n));
                if (!stepEl) return true;
                const fields = Array.from(stepEl.querySelectorAll('input, select, textarea'));
                const invalid = fields.find((el) => !el.checkValidity());
                if (invalid) {
                    invalid.reportValidity();
                    return false;
                }
                return true;
            };

            form.addEventListener('click', (e) => {
                const next = e.target.closest('[data-step-next]');
                const prev = e.target.closest('[data-step-prev]');
                if (!next && !prev) return;

                e.preventDefault();
                const current = Number(input?.value || 1);

                if (next && !validateStep(current)) return;

                const target = next ? Math.min(current + 1, 3) : Math.max(current - 1, 1);
                showStep(target);
            });

            showStep(Number(input?.value || 1));
        })();
    </script>
</x-layouts::auth>
