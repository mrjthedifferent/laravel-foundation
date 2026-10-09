@extends('notification::layouts.push-notification')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('notification::notification.push_notification_index.breadcrumb') }}</span>
@endsection

@section('content')
    <x-table-view-pagination title="{{ __('notification::notification.push_notification_index.title') }}" :data="$notifications" empty-icon="ph ph-paper-plane-tilt" empty-message="{{ __('notification::notification.push_notification_index.empty') }}">
        <x-slot name="actions">
            <x-table-actions>
                @can('Create Push Notification')
                    <x-table-action :href="route('admin.push.notification.create')" icon="ph ph-paper-plane-tilt" title="{{ __('notification::notification.push_notification_index.send_notification') }}" />
                @endcan
            </x-table-actions>
        </x-slot>

        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('notification::notification.push_notification_index.col_title') }}</th>
                <th>{{ __('notification::notification.push_notification_index.col_body') }}</th>
                <th>{{ __('notification::notification.push_notification_index.col_recipient') }}</th>
                <th>{{ __('notification::notification.push_notification_index.col_image') }}</th>
                <th>{{ __('notification::notification.push_notification_index.col_sent_at') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notifications as $notification)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="font-semibold">{{ $notification->title }}</td>
                    <td class="text-sm text-muted">{{ Str::limit($notification->body, 60) }}</td>
                    <td>
                        @if ($notification->user)
                            <span class="badge badge-info">{{ $notification->user->name }}</span>
                        @else
                            <span class="badge badge-secondary">{{ __('notification::notification.push_notification_index.all_users') }}</span>
                        @endif
                    </td>
                    <td>
                        @if ($notification->image)
                            <x-image :src="$notification->image" alt="{{ __('notification::notification.push_notification_index.col_image') }}" maxWidth="40" />
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap text-sm">{{ $notification->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </x-table-view-pagination>
@endsection
