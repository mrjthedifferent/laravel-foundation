@php use Illuminate\Contracts\Auth\MustVerifyEmail; @endphp
<section>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('admin.profile.update') }}" class="grid gap-4" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('foundation::foundation.profile.full_name')"/>
            <x-text-input id="name" name="name" type="text" class="block w-full"
                          :value="old('name', $user->name)"
                          required autofocus autocomplete="name"/>
            <x-input-error class="mt-1" :messages="$errors->get('name')"/>
        </div>

        @if ($user->phone)
        <div>
            <x-input-label for="phone" :value="__('foundation::foundation.profile.mobile_no')"/>
            <x-text-input id="phone" name="phone" type="text" class="block w-full" disabled :value="$user->phone"/>
        </div>
        @endif

        <div>
            <x-input-label for="email" :value="__('foundation::foundation.profile.email')"/>
            <x-text-input id="email" name="email" type="email" class="block w-full" disabled
                          :value="old('email', $user->email)" required autocomplete="username"/>
            <x-input-error class="mt-1" :messages="$errors->get('email')"/>

            @if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('foundation::foundation.profile.email_unverified') }}

                        <button form="send-verification"
                                class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('foundation::foundation.profile.resend_verification_link') }}
                        </button>
                    </p>

                    @if (session('success') === 'Verification link sent successfully')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('foundation::foundation.profile.verification_link_sent') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-form.file name="image" id="image" accept="image/jpeg,image/png,image/webp"
                :label="__('foundation::foundation.profile.image')" :current="$user->image" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('foundation::foundation.common.save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('foundation::foundation.profile.saved') }}</p>
            @endif
        </div>
    </form>
</section>
