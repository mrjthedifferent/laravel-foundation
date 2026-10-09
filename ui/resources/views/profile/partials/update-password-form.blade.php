<section>
    <form method="post" action="{{ route('password.update') }}" class="fd-fields fd-fields-3">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" :value="__('foundation::foundation.profile.current_password')" />
            <div class="relative">
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="block w-full pe-12" autocomplete="current-password" />
                <button type="button" aria-label="{{ __('foundation::foundation.auth.show_password') }}" class="btn btn-ghost btn-icon pw-toggle absolute top-1/2 end-0 -translate-y-1/2" data-target="update_password_current_password" tabindex="-1">
                    <i class="ph ph-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" :value="__('foundation::foundation.profile.new_password')" />
            <div class="relative">
                <x-text-input id="update_password_password" name="password" type="password" class="block w-full pe-12" autocomplete="new-password" />
                <button type="button" aria-label="{{ __('foundation::foundation.auth.show_password') }}" class="btn btn-ghost btn-icon pw-toggle absolute top-1/2 end-0 -translate-y-1/2" data-target="update_password_password" tabindex="-1">
                    <i class="ph ph-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('foundation::foundation.profile.confirm_password')" />
            <div class="relative">
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="block w-full pe-12" autocomplete="new-password" />
                <button type="button" aria-label="{{ __('foundation::foundation.auth.show_password') }}" class="btn btn-ghost btn-icon pw-toggle absolute top-1/2 end-0 -translate-y-1/2" data-target="update_password_password_confirmation" tabindex="-1">
                    <i class="ph ph-eye"></i>
                </button>
            </div>
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="fd-fields-wide flex items-center gap-4">
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
                    icon.removeClass('ph ph-eye').addClass('ph ph-eye-slash');
                } else {
                    input.type = 'password';
                    icon.removeClass('ph ph-eye-slash').addClass('ph ph-eye');
                }
            });
        });
    </script>
    @endpush
</section>