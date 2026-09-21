/**
 * Subscribes authenticated users to their private channel and the online presence channel.
 * Enables real-time notifications and active user count.
 *
 * Requires window.__USER_ID__ to be set by the layout when the user is authenticated.
 */

function hasReverbEnabled() {
    const key = window.__REVERB__?.key ?? import.meta.env.VITE_REVERB_APP_KEY;
    return key && String(key).trim();
}

export function initEchoSubscriptions() {
    if (!hasReverbEnabled() || typeof window.Echo === 'undefined') {
        return;
    }

    const userId = window.__USER_ID__;
    if (!userId) {
        return;
    }

    subscribeToPrivateChannel(userId);
    subscribeToPresenceChannel();
}

function subscribeToPrivateChannel(userId) {
    const channelName = `App.Models.User.${userId}`;

    window.Echo.private(channelName).notification((notification) => {
        try {
            if (notification == null || typeof notification !== 'object') {
                return;
            }
            const payload = notification?.data ?? notification?.payload ?? notification;
            const merged = Object.assign(
                {},
                typeof payload === 'object' && payload !== null ? payload : {},
                notification,
            );
            window.dispatchEvent(
                new CustomEvent('echo:notification', {
                    detail: merged,
                }),
            );
        } catch (err) {
            console.warn('[Echo] Notification handler error:', err);
        }
    });
}

const MAX_DROPDOWN_USERS = 15;

function formatCompactCount(n) {
    if (n >= 1_000_000) {
        return (n / 1_000_000).toFixed(1).replace(/\.0$/, '') + 'M';
    }
    if (n >= 1_000) {
        return (n / 1_000).toFixed(1).replace(/\.0$/, '') + 'K';
    }
    return String(n);
}

function subscribeToPresenceChannel() {
    let onlineUsers = [];

    function updateOnlineDisplay(users) {
        onlineUsers = Array.isArray(users) ? users : [];
        const count = onlineUsers.length;

        const wrapper = document.getElementById('online-user-count');
        const countEl = wrapper?.querySelector('.online-count');
        if (wrapper && countEl) {
            countEl.textContent = formatCompactCount(count);
            wrapper.title = `${count.toLocaleString()} user(s) online`;
        }

        const listEl = document.getElementById('online-users-list');
        const emptyEl = listEl?.querySelector('.online-users-empty');
        const itemsEl = document.getElementById('online-users-items');
        const moreEl = document.getElementById('online-users-more');
        if (listEl && emptyEl && itemsEl) {
            if (count === 0) {
                emptyEl.classList.remove('d-none');
                itemsEl.classList.add('d-none');
                if (moreEl) moreEl.classList.add('d-none');
                itemsEl.innerHTML = '';
            } else {
                emptyEl.classList.add('d-none');
                itemsEl.classList.remove('d-none');
                const displayed = onlineUsers.slice(0, MAX_DROPDOWN_USERS);
                const remaining = count - displayed.length;
                itemsEl.innerHTML = displayed
                    .map((u) => {
                        const name = (u?.name ?? `User ${u?.id ?? ''}`).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                        return `<div class="d-flex align-items-center gap-2 py-1"><span class="rounded-circle bg-success p-1" style="width:8px;height:8px;min-width:8px;"></span><span>${name}</span></div>`;
                    })
                    .join('');
                if (moreEl) {
                    if (remaining > 0) {
                        moreEl.classList.remove('d-none');
                        moreEl.textContent = `and ${remaining.toLocaleString()} more`;
                    } else {
                        moreEl.classList.add('d-none');
                    }
                }
            }
        }
    }

    window.Echo.join('online')
        .here((users) => {
            updateOnlineDisplay(users);
        })
        .joining((user) => {
            onlineUsers = [...onlineUsers.filter((u) => u?.id !== user?.id), user];
            updateOnlineDisplay(onlineUsers);
        })
        .leaving((user) => {
            onlineUsers = onlineUsers.filter((u) => u?.id !== user?.id);
            updateOnlineDisplay(onlineUsers);
        });
}
