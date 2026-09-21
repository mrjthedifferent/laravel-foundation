@extends('notification::layouts.push-notification')

@section('breadcrumb')
    <span class="breadcrumb-item active">Push Notification List</span>
@endsection

@section('content')
    <x-table-view-pagination title="Push Notifications" :data="$notifications" empty-icon="ph-paper-plane-tilt" empty-message="No push notifications sent yet">
        <x-slot name="actions">
            <x-table-actions>
                @can('Create Push Notification')
                    <x-table-action :href="route('admin.push.notification.create')" icon="ph-paper-plane-tilt" title="Send Notification" />
                @endcan
            </x-table-actions>
        </x-slot>

        <thead>
            <tr>
                <th style="width:44px">#</th>
                <th>Title</th>
                <th>Body</th>
                <th>Recipient</th>
                <th>Image</th>
                <th>Sent At</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notifications as $notification)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="fw-semibold">{{ $notification->title }}</td>
                    <td class="fs-sm text-muted">{{ Str::limit($notification->body, 60) }}</td>
                    <td>
                        @if ($notification->user)
                            <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $notification->user->name }}</span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">All Users</span>
                        @endif
                    </td>
                    <td>
                        @if ($notification->image)
                            <x-image :src="$notification->image" alt="Image" maxWidth="40" />
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-nowrap fs-sm">{{ $notification->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </x-table-view-pagination>
@endsection
