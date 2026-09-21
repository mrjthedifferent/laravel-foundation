<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <div class="position-relative mt-1">
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="block w-full pe-5" autocomplete="current-password" />
                <button type="button" class="btn border-0 pw-toggle text-muted shadow-none position-absolute top-50 end-0 translate-middle-y" data-target="update_password_current_password" tabindex="-1">
                    <i class="ph-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <div class="position-relative mt-1">
                <x-text-input id="update_password_password" name="password" type="password" class="block w-full pe-5" autocomplete="new-password" />
                <button type="button" class="btn border-0 pw-toggle text-muted shadow-none position-absolute top-50 end-0 translate-middle-y" data-target="update_password_password" tabindex="-1">
                    <i class="ph-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <div class="position-relative mt-1">
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="block w-full pe-5" autocomplete="new-password" />
                <button type="button" class="btn border-0 pw-toggle text-muted shadow-none position-absolute top-50 end-0 translate-middle-y" data-target="update_password_password_confirmation" tabindex="-1">
                    <i class="ph-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4 mt-2">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="text-sm text-gray-600">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

    @push('scripts')
    <script>
        $(document).ready(function() {
            // Toggle password visibility
            $(document).off('click', '.pw-toggle').on('click', '.pw-toggle', function() {
                var input = document.getElementById($(this).data('target'));
                var icon = $(this).find('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.removeClass('ph-eye').addClass('ph-eye-slash');
                } else {
                    input.type = 'password';
                    icon.removeClass('ph-eye-slash').addClass('ph-eye');
                }
            });
        });
    </script>
    @endpush
</section>