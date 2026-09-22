@php use Illuminate\Contracts\Auth\MustVerifyEmail; @endphp
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('foundation::foundation.profile.information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('foundation::foundation.profile.information_notice') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('admin.profile.update') }}" class="mt-6 space-y-6" enctype="multipart/form-data">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('foundation::foundation.profile.full_name')"/>
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                          :value="old('name', $user->name)"
                          required autofocus autocomplete="name"/>
            <x-input-error class="mt-2" :messages="$errors->get('name')"/>
        </div>

        @if ($user->phone)
        <div>
            <x-input-label for="phone" :value="__('foundation::foundation.profile.mobile_no')"/>
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" disabled :value="$user->phone"/>
        </div>
        @endif

        <div>
            <x-input-label for="email" :value="__('foundation::foundation.profile.email')"/>
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" disabled
                          :value="old('email', $user->email)" required autocomplete="username"/>
            <x-input-error class="mt-2" :messages="$errors->get('email')"/>

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
            <x-input-label for="image" :value="__('foundation::foundation.profile.image')"/>
            <div class="mt-1 flex items-center">
                @if ($user->image)
                    <img src="{{ $user->image }}" alt="{{ $user->name }}" class="fd-avatar fd-avatar-lg"/>
                @endif
                <input id="image" name="image" type="file" class="mt-1 block w-full"/>
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('image')"/>
        </div>

        <div class="flex items-center gap-4 mt-2">
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
