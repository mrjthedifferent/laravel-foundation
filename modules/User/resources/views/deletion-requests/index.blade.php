@extends('user::layouts.master')

@section('breadcrumb')
<span class="breadcrumb-item active">{{ __('user::user.deletion.menu') }}</span>
@endsection

@section('content')
<div class="alert {{ $needsReview ? 'alert-warning' : 'alert-info' }} mb-4" role="status">
    <i class="ph ph-info"></i>
    {{ $needsReview ? __('user::user.deletion.mode_review') : __('user::user.deletion.mode_automatic') }}
    @can('editSpecial', \Modules\Settings\Models\Setting::class)
        <a href="{{ route('admin.settings.special.security') }}" class="ms-1">{{ __('settings::settings.special_security.title') }}</a>
    @endcan
</div>

<x-table-view-pagination
    title="{{ __('user::user.deletion.page_title') }}"
    :data="$requests"
    empty-message="{{ __('user::user.deletion.empty') }}"
    empty-icon="ph ph-user-minus">
    <x-slot name="tabs">
        @foreach (['pending_review', 'scheduled', 'history'] as $name)
            <li class="nav-item">
                <a class="nav-link {{ $tab === $name ? 'active' : '' }}" href="{{ route('admin.deletion-requests.index', ['tab' => $name]) }}">
                    {{ __("user::user.deletion.tabs.{$name}") }}
                    @if (! empty($counts[$name]))
                        <span class="badge {{ $name === 'pending_review' ? 'badge-warning' : 'badge-secondary' }} ms-1">{{ $counts[$name] }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </x-slot>
    <thead>
        <tr>
            <th>{{ __('user::user.deletion.col_user') }}</th>
            <th>{{ __('user::user.deletion.col_requested') }}</th>
            <th>{{ __('user::user.deletion.col_source') }}</th>
            @if ($tab === 'history')
                <th>{{ __('user::user.deletion.col_status') }}</th>
                <th>{{ __('user::user.deletion.col_reviewer') }}</th>
                <th>{{ __('user::user.deletion.col_reason') }}</th>
            @else
                <th>{{ __('user::user.deletion.col_scheduled') }}</th>
                <th>{{ __('user::user.deletion.col_blockers') }}</th>
                <th class="text-end">{{ __('user::user.deletion.col_actions') }}</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach ($requests as $row)
        <tr>
            <td>
                @if ($row->user->anonymized_at === null && Route::has('admin.users.show'))
                    <a href="{{ route('admin.users.show', $row->user) }}" class="font-semibold">{{ $row->user->name }}</a>
                @else
                    <span class="font-semibold">{{ $row->user->name }}</span>
                @endif
                <div class="text-muted small">{{ $row->user->phone ?? $row->user->email ?? '#'.$row->user_id }}</div>
            </td>
            <td class="whitespace-nowrap small">
                {{ $row->requested_at->format('d M Y, h:i A') }}
                <div class="text-muted">{{ $row->requested_at->diffForHumans() }}</div>
            </td>
            <td class="small">{{ __("user::user.deletion.source.{$row->source}") }}</td>
            @if ($tab === 'history')
                <td>
                    <span class="fd-status {{ $row->status->value === 'done' ? 'is-danger' : 'is-success' }}">{{ $row->status->label() }}</span>
                    <div class="text-muted small">{{ ($row->completed_at ?? $row->reviewed_at ?? $row->updated_at)?->format('d M Y') }}</div>
                </td>
                <td class="small">{{ $row->reviewer?->name ?? '—' }}</td>
                <td class="small"><x-truncated-text :text="$row->reason ?? '—'" :limit="60" /></td>
            @else
                <td class="whitespace-nowrap small">
                    @if ($row->scheduled_for)
                        {{ $row->scheduled_for->format('d M Y') }}
                        <div class="text-muted">{{ $row->scheduled_for->diffForHumans() }}</div>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="small">
                    @forelse ($blockers[$row->id] ?? [] as $blocker)
                        <div class="text-danger"><i class="ph ph-warning"></i> {{ $blocker }}</div>
                    @empty
                        <span class="text-muted">{{ __('user::user.deletion.no_blockers') }}</span>
                    @endforelse
                </td>
                <td class="text-end whitespace-nowrap">
                    @if ($row->status === \Modules\User\Enum\DeletionStatus::PendingReview)
                        <form action="{{ route('admin.deletion-requests.approve', $row) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success swal-confirm" data-text="{{ __('user::user.deletion.confirm_approve') }}">
                                <i class="ph ph-check"></i>{{ __('user::user.deletion.approve') }}
                            </button>
                        </form>
                    @endif
                    <button type="button" class="btn btn-sm btn-outline-secondary js-reject"
                        data-url="{{ route('admin.deletion-requests.reject', $row) }}"
                        data-fd-toggle="modal" data-fd-target="#rejectDeletionModal">
                        <i class="ph ph-x"></i>{{ __('user::user.deletion.reject') }}
                    </button>
                    @can(\Modules\User\Services\AccountDeletion::ANONYMIZE_PERMISSION)
                        @if (empty($blockers[$row->id]))
                            <form action="{{ route('admin.deletion-requests.anonymize', $row) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger swal-confirm" data-text="{{ __('user::user.deletion.confirm_anonymize') }}">
                                    <i class="ph ph-trash"></i>{{ __('user::user.deletion.anonymize_now') }}
                                </button>
                            </form>
                        @endif
                    @endcan
                </td>
            @endif
        </tr>
        @endforeach
    </tbody>
</x-table-view-pagination>

<x-modal id="rejectDeletionModal" :title="__('user::user.deletion.reject')">
    <form method="POST" id="rejectDeletionForm">
        @csrf
        <x-form.textarea name="reason" :label="__('user::user.deletion.reject_reason')" rows="3" required maxlength="500" />
        <div class="mt-4 text-end">
            <button type="submit" class="btn btn-danger"><i class="ph ph-x"></i>{{ __('user::user.deletion.reject') }}</button>
        </div>
    </form>
</x-modal>
@endsection

@push('scripts')
<script>
$(function () {
    $(document).on('click', '.js-reject', function () {
        $('#rejectDeletionForm').attr('action', $(this).data('url'));
    });
});
</script>
@endpush
