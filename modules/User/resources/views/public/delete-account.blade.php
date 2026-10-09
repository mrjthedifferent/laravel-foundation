@extends('layouts.public')

@section('title', __('user::user.account_deletion.title'))

@section('content')
    <div class="fd-prose">
        <h1>{{ __('user::user.account_deletion.title') }}</h1>

        @if (session('account_deleted'))
            <div class="alert alert-success" role="status">
                <i class="ph ph-check-circle"></i> {{ __('user::user.account_deletion.done') }}
            </div>
        @else
            <p>{{ __('user::user.account_deletion.intro') }}</p>
            <p><strong>{{ __('user::user.account_deletion.what') }}</strong></p>
            <ul>
                @foreach (array_filter(array_map('trim', explode("\n", __('user::user.account_deletion.items')))) as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
            <p><strong>{{ __('user::user.account_deletion.permanent') }}</strong></p>
            <p>{{ __('user::user.account_deletion.in_app') }}</p>
        @endif
    </div>

    @unless (session('account_deleted'))
        <form method="POST" action="{{ route('account.delete.destroy') }}" class="mt-6" novalidate>
            @csrf
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12">
                    <x-form.input name="login" :label="__('user::user.account_deletion.login')" :value="old('login')" required autocomplete="username" />
                </div>
                <div class="col-span-12">
                    <x-form.input name="password" type="password" :label="__('user::user.account_deletion.password')" required autocomplete="current-password" />
                </div>
                <div class="col-span-12">
                    <x-form.input name="two_factor_code" :label="__('user::user.account_deletion.two_factor')" autocomplete="one-time-code" />
                </div>
                <div class="col-span-12">
                    <x-form.checkbox name="confirm" :label="__('user::user.account_deletion.confirm')" />
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="btn btn-danger">
                    <i class="ph ph-trash"></i>{{ __('user::user.account_deletion.submit') }}
                </button>
            </div>
        </form>
    @endunless
@endsection
