@extends('layouts.public')

@section('title', __('user::user.account_deletion.title'))

@section('content')
    @php($deletion = session('deletion'))
    <div class="fd-prose">
        <h1>{{ __('user::user.account_deletion.title') }}</h1>

        @if ($deletion)
            <div class="alert alert-success" role="status">
                <i class="ph ph-check-circle"></i>
                @if ($deletion['review'])
                    {{ __('user::user.account_deletion.done_review') }}
                @else
                    {{ __('user::user.account_deletion.done_scheduled', ['date' => $deletion['date']]) }}
                @endif
            </div>
            <p>{{ __('user::user.account_deletion.cancel_hint') }}</p>
        @else
            @if (session('deletion_blockers'))
                <div class="alert alert-warning" role="alert">
                    <p><strong><i class="ph ph-warning"></i> {{ __('user::user.deletion.blocked') }}</strong></p>
                    <ul>
                        @foreach (session('deletion_blockers') as $blocker)
                            <li>{{ $blocker }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <p>{{ __('user::user.account_deletion.intro') }}</p>
            <p><strong>{{ __('user::user.account_deletion.what') }}</strong></p>
            <ul>
                @foreach (array_filter(array_map('trim', explode("\n", __('user::user.account_deletion.items')))) as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
            <p><strong>{{ __('user::user.account_deletion.kept') }}</strong></p>
            <ul>
                @foreach (array_filter(array_map('trim', explode("\n", __('user::user.account_deletion.kept_items')))) as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
            <p>{{ trans_choice('user::user.account_deletion.grace', $graceDays, ['days' => $graceDays]) }}</p>
            <p>{{ __('user::user.account_deletion.in_app') }}</p>
        @endif
    </div>

    @unless ($deletion)
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
