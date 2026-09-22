@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('settings::settings.special_notifications.breadcrumb') }}</span>
@endsection

@section('content')
    <form action="{{ route('admin.settings.special.update_notifications') }}" method="POST">
        @csrf

    <x-page-header title="{{ __('settings::settings.special_notifications.title') }}"
        subtitle="{{ __('settings::settings.special_notifications.subtitle') }}"
        icon="ph-bell-ringing">
        <x-slot name="actions">
            <button type="submit" class="btn btn-primary px-4">
                <i class="ph-floppy-disk"></i>{{ __('settings::settings.special_notifications.save_changes') }}
            </button>
        </x-slot>
    </x-page-header>

    @foreach ($groups as $groupLabel => $rows)
        <x-form-section :title="display_label($groupLabel)" icon="ph-bell-simple">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('settings::settings.special_notifications.col_notification') }}</th>
                            @foreach ($channels as $channel => $channelLabel)
                                <th class="text-center text-nowrap">
                                    {{ $channelLabel }}
                                    @if ($channel === 'database')
                                        <i class="ph-info fs-sm text-muted" data-bs-popup="tooltip"
                                            title="{{ __('settings::settings.special_notifications.database_tooltip') }}"></i>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="fw-semibold fs-sm">{{ display_label($row['label']) }}</td>
                                @foreach ($channels as $channel => $channelLabel)
                                    @php($cell = $row['cells'][$channel])
                                    <td class="text-center">
                                        @if (! $cell['supported'])
                                            <span class="text-muted">&mdash;</span>
                                        @elseif ($cell['locked'])
                                            <div class="form-check form-switch d-inline-block mb-0"
                                                data-bs-popup="tooltip" title="{{ __('settings::settings.special_notifications.locked_tooltip') }}">
                                                <input type="checkbox" class="form-check-input" role="switch"
                                                    checked disabled>
                                            </div>
                                        @else
                                            <div class="form-check form-switch d-inline-block mb-0">
                                                <input type="checkbox" class="form-check-input" role="switch"
                                                    id="{{ $cell['key'] }}" name="{{ $cell['key'] }}" value="1"
                                                    {{ $cell['enabled'] ? 'checked' : '' }}>
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-form-section>
    @endforeach

    <div class="d-flex justify-content-end">
        <button type="submit" class="btn btn-primary px-5">
            <i class="ph-floppy-disk"></i>{{ __('settings::settings.special_notifications.save_changes') }}
        </button>
    </div>

    </form>
@endsection
