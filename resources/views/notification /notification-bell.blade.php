<!-- resources/views/components/notification-bell.blade.php -->
@php
    use Illuminate\Support\Str;
@endphp

<div class="relative" x-data="{
    open: false,
    notifications: [],
    unreadCount: 0,
    loading: false,

    fetchNotifications() {
        this.loading = true;
        fetch('/notifications/json')
            .then(response => response.json())
            .then(data => {
                this.notifications = data.notifications;
                this.unreadCount = data.unread_count;
                this.loading = false;
            })
            .catch(error => {
                console.error('Error fetching notifications:', error);
                this.loading = false;
            });
    },

    markAsRead(notificationId, actionUrl) {
        fetch(`/notifications/${notificationId}/mark-as-read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                'Content-Type': 'application/json'
            }
        }).then(() => {
            // Update local state
            const notification = this.notifications.find(n => n.id === notificationId);
            if (notification) {
                notification.is_read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            }

            // Navigate to the URL
            if (actionUrl) {
                window.location.href = actionUrl;
            }
        }).catch(error => {
            console.error('Error marking notification as read:', error);
            // Still navigate even if marking fails
            if (actionUrl) {
                window.location.href = actionUrl;
            }
        });
    },

    markAllAsRead() {
        fetch('/notifications/mark-all-as-read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                'Content-Type': 'application/json'
            }
        }).then(() => {
            this.notifications.forEach(n => n.is_read = true);
            this.unreadCount = 0;
        });
    },

    getIcon(type) {
        const icons = {
            'meeting': 'fa-calendar-alt text-blue-500',
            'team': 'fa-users text-green-500',
            'task': 'fa-tasks text-purple-500',
            'bulk': 'fa-bullhorn text-yellow-500'
        };
        return icons[type] || 'fa-bell text-gray-500';
    }
}" x-init="fetchNotifications();
setInterval(() => fetchNotifications(), 30000)">

    <button @click="open = !open; if(!open) fetchNotifications()"
        class="text-gray-500 hover:text-gray-700 relative focus:outline-none">
        <i class="fas fa-bell text-xl"></i>
        <span x-show="unreadCount > 0" x-cloak id="notification-badge"
            class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-[1.25rem] h-5 flex items-center justify-center px-1"
            x-text="unreadCount > 99 ? '99+' : unreadCount">
        </span>
    </button>

    <!-- Dropdown -->
    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl z-50 notifications-dropdown overflow-hidden">

        <!-- Header -->
        <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-bell mr-2 text-indigo-600"></i>
                    Notifications
                </h3>
                <button x-show="unreadCount > 0" @click="markAllAsRead()"
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                    Mark all as read
                </button>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="max-h-96 overflow-y-auto divide-y divide-gray-100">
            <template x-if="loading">
                <div class="p-8 text-center">
                    <i class="fas fa-spinner fa-spin text-indigo-600 text-2xl"></i>
                    <p class="mt-2 text-gray-500">Loading notifications...</p>
                </div>
            </template>

            <template x-if="!loading && notifications.length === 0">
                <div class="p-8 text-center">
                    <i class="fas fa-bell-slash text-gray-400 text-4xl mb-3"></i>
                    <p class="text-gray-500">No notifications yet</p>
                    <p class="text-xs text-gray-400 mt-1">You'll see notifications here when you get added to meetings,
                        teams, or tasks</p>
                </div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <div class="hover:bg-gray-50 transition duration-150 cursor-pointer"
                    :class="{ 'bg-blue-50': !notification.is_read, 'bg-white': notification.is_read }"
                    @click="markAsRead(notification.id, notification.action_url)">
                    <div class="p-4">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <i class="fas text-xl" :class="getIcon(notification.type)"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800" x-text="notification.message"></p>
                                <div class="flex items-center justify-between mt-2">
                                    <p class="text-xs text-gray-500" x-text="notification.created_at"></p>
                                    <span x-show="!notification.is_read"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        New
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Footer -->
        <div x-show="notifications.length > 0" class="p-2 border-t border-gray-200 bg-gray-50">
            <a href="{{ route('notification.index') }}"
                class="block text-center text-sm text-indigo-600 hover:text-indigo-800 py-2">
                View all notifications
                <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>
