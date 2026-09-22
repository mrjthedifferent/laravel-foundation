<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('foundation::foundation.profile.update_password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('foundation::foundation.profile.update_password_notice') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('foundation::foundation.profile.current_password')" />
            <div class="position-relative mt-1">
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="block w-full pe-5" autocomplete="current-password" />
                <button type="button" aria-label="{{ __('foundation::foundation.auth.show_password') }}" class="btn btn-ghost btn-icon pw-toggle position-absolute top-50 end-0 translate-middle-y" data-target="update_password_current_password" tabindex="-1">
                    <i class="ph-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('foundation::foundation.profile.new_password')" />
            <div class="position-relative mt-1">
                <x-text-input id="update_password_password" name="password" type="password" class="block w-full pe-5" autocomplete="new-password" />
                <button type="button" aria-label="{{ __('foundation::foundation.auth.show_password') }}" class="btn btn-ghost btn-icon pw-toggle position-absolute top-50 end-0 translate-middle-y" data-target="update_password_password" tabindex="-1">
                    <i class="ph-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('foundation::foundation.profile.confirm_password')" />
            <div class="position-relative mt-1">
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="block w-full pe-5" autocomplete="new-password" />
                <button type="button" aria-label="{{ __('foundation::foundation.auth.show_password') }}" class="btn btn-ghost btn-icon pw-toggle position-absolute top-50 end-0 translate-middle-y" data-target="update_password_password_confirmation" tabindex="-1">
                    <i class="ph-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4 mt-2">
            <x-primary-button>{{ __('foundation::foundation.common.save') }}</x-primary-button>

            @if (session('status') === 'password-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="text-sm text-gray-600">{{ __('foundation::foundation.profile.saved') }}</p>
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