{{-- Maintenance mode (EnsurePanelIsAvailable): the maintenance_mode setting is on. --}}
@extends('errors.minimal')

@section('title', __('foundation::foundation.errors.maintenance'))
@section('code', '503')
@section('message', __('foundation::foundation.errors.maintenance'))
@section('description', $maintenanceMessage)

{{-- A home link would lead straight back here; a signed-in user gets a way out instead. --}}
@auth
    @section('actions')
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="ph ph-sign-out"></i>{{ __('foundation::foundation.errors.sign_out') }}
            </button>
        </form>
    @endsection
@endauth
