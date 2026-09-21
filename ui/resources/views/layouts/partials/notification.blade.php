<!-- Notifications -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="notifications">
    <div class="offcanvas-header py-0">
        <h5 class="offcanvas-title py-3">Notifications</h5>
        <button type="button" class="btn btn-light btn-sm btn-icon border-transparent rounded-pill"
            data-bs-dismiss="offcanvas">
            <i class="ph-x"></i>
        </button>
    </div>

    <div class="offcanvas-body p-0">
        <div class="bg-body-tertiary fw-medium py-2 px-3">New notifications</div>
        <div class="p-3" id="notification-list">
            {{--            <div class="d-flex align-items-start mb-3"> --}}
            {{--                <a href="#" class="status-indicator-container me-3"> --}}
            {{--                    <img src="{{asset('images/default.png')}}" class="w-40px h-40px rounded-pill" --}}
            {{--                         alt=""> --}}
            {{--                    <span class="status-indicator bg-danger"></span> --}}
            {{--                </a> --}}
            {{--                <div class="flex-fill"> --}}
            {{--                    <a href="#" class="fw-semibold">Example</a> --}}
            {{--                    <div class="fs-sm text-muted mt-1">2 days ago</div> --}}
            {{--                </div> --}}
            {{--            </div> --}}
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- View All button like Acitivity -->
    <div class="offcanvas-footer p-3 d-flex">
        <button type="button" class="me-2 btn btn-danger w-100 swal-post"
            data-url="{{ route('admin.notification.mark-all-as-read') }}"
            data-method="PATCH"
            data-text="Are you sure you want to mark all as read?">Mark All as Read</button>
        <a href="{{ route('admin.notification.index') }}" class="ms-2 btn btn-secondary w-100">View All</a>
    </div>
</div>
<!-- /notifications -->

@push('scripts')
    <script>
        // Function to load new notifications
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
                    // Show error state in notification list
                    $('#notification-list').html(`
                        <div class="text-center text-muted py-3">
                            <i class="ph-warning-circle ph-2x mb-2"></i>
                            <p class="mb-0">Failed to load notifications</p>
                        </div>
                    `);
                }
            });
        }

        // Function to update notification list
        function updateNotificationList(notifications) {
            const notificationList = $('#notification-list');
            const list = Array.isArray(notifications) ? notifications : [];

            if (list.length === 0) {
                notificationList.html(`
                    <div class="text-center text-muted py-3">
                        <i class="ph-bell-slash ph-2x mb-2"></i>
                        <p class="mb-0">No new notifications</p>
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
                    <div class="d-flex align-items-start mb-3 notification-item" data-id="${notification.id}">
                        <div class="flex-shrink-0">
                            <div class="status-indicator-container">
                                <div class="notification-icon bg-${getNotificationColor(notification.type)} bg-opacity-10 rounded-circle p-2">
                                    <i class="ph-${getNotificationIcon(notification.type)} text-${getNotificationColor(notification.type)}"></i>
                                </div>
                                ${!isRead ? '<span class="status-indicator bg-danger"></span>' : ''}
                            </div>
                        </div>
                        <div class="flex-fill ms-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1 fw-semibold">${notificationData.title || 'Notification'}</h6>
                                    <p class="text-muted mb-1 small">${notificationData.body || 'No message'}</p>
                                </div>
                                <div class="flex-shrink-0">
                                    <small class="text-muted">${timeAgo}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            notificationList.html(html);
        }

        // Function to update notification count in header
        function updateNotificationCount(notifications) {
            const list = Array.isArray(notifications) ? notifications : [];
            const unreadCount = list.filter(n => n.read_at === null).length;
            const badge = $('#notification-count');

            if (unreadCount > 0) {
                badge.text(unreadCount).show();
            } else {
                badge.hide();
            }
        }

        // Helper function to get notification color based on type
        function getNotificationColor(type) {
            switch (type) {
                case 'success':
                    return 'success';
                case 'error':
                    return 'danger';
                case 'warning':
                    return 'warning';
                default:
                    return 'info';
            }
        }

        // Helper function to get notification icon based on type
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
            // Load notifications immediately
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

            // Handle individual notification clicks
            $(document).on('click', '.notification-item', function() {
                const notificationId = $(this).data('id');
                if (notificationId) {
                    // Mark as read and redirect to notification view
                    $.ajax({
                        url: `{{ url('notification') }}/${notificationId}/mark-as-read`,
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function() {
                            // Reload notifications to update the list
                            loadNewNotification();
                        }
                    });
                }
            });
        });
    </script>

    <style>
        .notification-item {
            cursor: pointer;
            transition: background-color 0.2s ease;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
        }

        .notification-item:hover {
            background-color: var(--gray-100);
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .status-indicator-container {
            position: relative;
        }

        .status-indicator {
            position: absolute;
            top: -2px;
            right: -2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid var(--body-bg);
        }

        #notification-count {
            font-size: 0.75rem;
            min-width: 18px;
            height: 18px;
            line-height: 14px;
            padding: 2px 6px;
        }
    </style>
@endpush
