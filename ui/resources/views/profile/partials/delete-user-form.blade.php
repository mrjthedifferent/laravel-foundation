<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-danger-button data-bs-toggle="modal" data-bs-target="#confirm-user-deletion">
        {{ __('Delete Account') }}
    </x-danger-button>

    <x-modal id="confirm-user-deletion" title="{{ __('Delete Account') }}" button-text="{{ __('Delete Account') }}">
        <form method="post" action="{{ route('admin.profile.destroy') }}">
            @csrf
            @method('delete')
            <div class="modal-body">
                <h2 class="text-lg font-medium text-gray-900">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>

                <div class="mt-6">
                    <x-input-label for="password" value="{{ __('Password') }}" class="sr-only"/>

                    <x-text-input
                        id="password"
                        name="password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="{{ __('Password') }}"
                    />

                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2"/>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-link" data-bs-dismiss="modal">Close</button>
                <x-danger-button>Delete Account</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
