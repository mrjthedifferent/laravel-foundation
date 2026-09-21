@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">Notification Settings</span>
@endsection

@section('content')
    {!! Form::open(['route' => 'admin.settings.special.update_notifications', 'method' => 'post']) !!}

    <x-page-header title="Notification Settings"
        subtitle="Control every delivery channel per notification. In-App also covers real-time (browser) delivery."
        icon="ph-bell-ringing">
        <x-slot name="actions">
            <button type="submit" class="btn btn-primary px-4">
                <i class="ph-floppy-disk me-1"></i>Save Changes
            </button>
        </x-slot>
    </x-page-header>

    @foreach ($groups as $groupLabel => $rows)
        <x-form-section :title="$groupLabel" icon="ph-bell-simple">
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-xs align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Notification</th>
                            @foreach ($channels as $channel => $channelLabel)
                                <th class="text-center" style="width:110px">
                                    {{ $channelLabel }}
                                    @if ($channel === 'database')
                                        <i class="ph-info fs-sm text-muted" data-bs-popup="tooltip"
                                            title="Also controls real-time (browser) delivery"></i>
                                    @endif
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="fw-semibold fs-sm">{{ $row['label'] }}</td>
                                @foreach ($channels as $channel => $channelLabel)
                                    @php($cell = $row['cells'][$channel])
                                    <td class="text-center">
                                        @if (! $cell['supported'])
                                            <span class="text-muted">&mdash;</span>
                                        @elseif ($cell['locked'])
                                            <div class="form-check form-switch d-inline-block mb-0"
                                                data-bs-popup="tooltip" title="Always on — required for phone OTP">
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
            <i class="ph-floppy-disk me-1"></i>Save Changes
        </button>
    </div>

    {!! Form::close() !!}
@endsection
