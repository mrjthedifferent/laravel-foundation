<!-- Notifications -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="notifications" aria-labelledby="notifications-title">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="notifications-title">{{ __('foundation::foundation.notification.title') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
            aria-label="{{ __('foundation::foundation.common.close') }}"></button>
    </div>

    <div class="offcanvas-body p-2" id="notification-list">
        <div class="text-center py-4">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">{{ __('foundation::foundation.notification.loading') }}</span>
            </div>
        </div>
    </div>

    <div class="offcanvas-footer p-2 d-flex gap-2">
        <button type="button" class="btn btn-ghost btn-sm swal-post"
            data-url="{{ route('admin.notification.mark-all-as-read') }}"
            data-method="PATCH"
            data-text="{{ __('foundation::foundation.notification.mark_all_read_confirm') }}">{{ __('foundation::foundation.notification.mark_all_read') }}</button>
        <a href="{{ route('admin.notification.index') }}" class="btn btn-light btn-sm ms-auto">{{ __('foundation::foundation.notification.view_all') }}</a>
    </div>
</div>
<!-- /notifications -->

@push('scripts')
    <script>
        function loadNewNotification() {
            $.ajax({
                url: '{{ route("admin.notification.index") }}',
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                data: {
                    per_page: 10,
                    read: false // Only get unread notifications
                },
                success: function(response) {
                    const notifications = Array.isArray(response.data) ? response.data : (response.data?.data ?? []);
                    updateNotificationList(notifications);
                    updateNotificationCount(notifications);
                },
                error: function(xhr, status, error) {
                    console.error('Error loading notifications:', error);
                    $('#notification-list').html(`
                        <div class="fd-empty">
                            <span class="fd-empty-icon"><i class="ph-warning-circle"></i></span>
                            <p class="fd-empty-title">{{ __('foundation::foundation.notification.failed_to_load') }}</p>
                        </div>
                    `);
                }
            });
        }

        function updateNotificationList(notifications) {
            const notificationList = $('#notification-list');
            const list = Array.isArray(notifications) ? notifications : [];

            if (list.length === 0) {
                notificationList.html(`
                    <div class="fd-empty">
                        <span class="fd-empty-icon"><i class="ph-bell-slash"></i></span>
                        <p class="fd-empty-title">{{ __('foundation::foundation.notification.empty') }}</p>
                    </div>
                `);
                return;
            }

            let html = '';
            list.forEach(function(notification) {
                const notificationData = notification.data || {};
                const isRead = notification.read_at !== null;
                const timeAgo = new Date(notification.created_at).toLocaleDateString();

                html += `
                    <div class="notification-item ${isRead ? '' : 'is-unread'}" data-id="${notification.id}">
                        <span class="fd-icon-tile fd-icon-tile-sm ${getNotificationTone(notification.type)}">
                            <i class="ph-${getNotificationIcon(notification.type)}"></i>
                        </span>
                        <div class="min-width-0 flex-fill">
                            <div class="fs-sm fw-semibold text-strong">${notificationData.title || @js(__('foundation::foundation.notification.default_title'))}</div>
                            <div class="fs-xs text-muted">${notificationData.body || @js(__('foundation::foundation.notification.default_body'))} · ${timeAgo}</div>
                        </div>
                    </div>
                `;
            });

            notificationList.html(html);
        }

        function updateNotificationCount(notifications) {
            const list = Array.isArray(notifications) ? notifications : [];
            const unreadCount = list.filter(n => n.read_at === null).length;
            // The dot hides itself at zero: see .fd-notify-dot[data-count="0"]
            $('#notification-count').attr('data-count', unreadCount);
        }

        function getNotificationTone(type) {
            switch (type) {
                case 'success':
                    return 'is-success';
                case 'error':
                    return 'is-danger';
                case 'warning':
                    return 'is-warning';
                default:
                    return 'is-info';
            }
        }

        function getNotificationIcon(type) {
            switch (type) {
                case 'success':
                    return 'check-circle';
                case 'error':
                    return 'x-circle';
                case 'warning':
                    return 'warning-circle';
                default:
                    return 'bell';
            }
        }

        $(document).ready(function() {
            loadNewNotification();

            // Real-time notification refresh and toast when broadcast is received
            window.addEventListener('echo:notification', function(e) {
                try {
                    const n = e?.detail;
                    if (!n || typeof n !== 'object') return;
                    const data = n.data ?? n.payload ?? {};
                    const title = n.title ?? data?.title ?? 'Notification';
                    const body = n.body ?? data?.body ?? data?.message ?? '';
                    const type = n.type ?? data?.type ?? 'info';
                    const icon = { success: 'success', error: 'error', warning: 'warning' }[type] || 'info';
                    if (typeof window.toast === 'function') {
                        window.toast(icon, title, body);
                    }
                    loadNewNotification();
                } catch (err) {
                    console.warn('[Notification] Handler error:', err);
                }
            });

            $(document).on('click', '.notification-item', function() {
                const notificationId = $(this).data('id');
                if (notificationId) {
                    $.ajax({
                        url: `{{ url('notification') }}/${notificationId}/mark-as-read`,
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function() {
                            loadNewNotification();
                        }
                    });
                }
            });
        });
    </script>
@endpush
