<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('foundation::foundation.profile.delete_account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('foundation::foundation.profile.delete_account_notice') }}
        </p>
    </header>

    <x-danger-button data-bs-toggle="modal" data-bs-target="#confirm-user-deletion">
        {{ __('foundation::foundation.profile.delete_account') }}
    </x-danger-button>

    <x-modal id="confirm-user-deletion" title="{{ __('foundation::foundation.profile.delete_account') }}" button-text="{{ __('foundation::foundation.profile.delete_account') }}">
        <form method="post" action="{{ route('admin.profile.destroy') }}">
            @csrf
            @method('delete')
            <div class="modal-body">
                <h2 class="text-lg font-medium text-gray-900">
                    {{ __('foundation::foundation.profile.delete_account_confirm_heading') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{ __('foundation::foundation.profile.delete_account_confirm_notice') }}
                </p>

                <div class="mt-6">
                    <x-input-label for="password" value="{{ __('foundation::foundation.profile.password') }}" class="sr-only"/>

                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="{{ __('foundation::foundation.profile.password') }}"
                    />

                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2"/>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">{{ __('foundation::foundation.common.close') }}</button>
                <x-danger-button>{{ __('foundation::foundation.profile.delete_account') }}</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
