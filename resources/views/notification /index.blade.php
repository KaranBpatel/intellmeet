@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-indigo-50 to-purple-50">
                    <div class="flex justify-between items-center">
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-bell mr-2 text-indigo-600"></i>
                            All Notifications
                        </h1>
                        <button id="markAllRead" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            Mark all as read
                        </button>
                    </div>
                </div>

                <div id="notifications-list" class="divide-y divide-gray-100">
                    <!-- Notifications will be loaded here -->
                    <div class="p-8 text-center">
                        <i class="fas fa-spinner fa-spin text-indigo-600 text-2xl"></i>
                        <p class="mt-2 text-gray-500">Loading notifications...</p>
                    </div>
                </div>

                <div id="pagination" class="p-4 border-t border-gray-200 bg-gray-50">
                    <!-- Pagination will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            let currentPage = 1;

            function loadNotifications(page = 1) {
                fetch(`/notifications/json?page=${page}`)
                    .then(response => response.json())
                    .then(data => {
                        renderNotifications(data.notifications);
                        renderPagination(data);
                        currentPage = page;
                    })
                    .catch(error => {
                        console.error('Error loading notifications:', error);
                        document.getElementById('notifications-list').innerHTML = `
                    <div class="p-8 text-center text-red-500">
                        <i class="fas fa-exclamation-circle text-4xl mb-3"></i>
                        <p>Error loading notifications. Please try again.</p>
                    </div>
                `;
                    });
            }

            function renderNotifications(notifications) {
                const container = document.getElementById('notifications-list');

                if (!notifications || notifications.length === 0) {
                    container.innerHTML = `
                <div class="p-8 text-center">
                    <i class="fas fa-bell-slash text-gray-400 text-4xl mb-3"></i>
                    <p class="text-gray-500">No notifications yet</p>
                    <p class="text-xs text-gray-400 mt-1">You'll see notifications here when you get added to meetings, teams, or tasks</p>
                </div>
            `;
                    return;
                }

                container.innerHTML = notifications.map(notification => `
            <div class="hover:bg-gray-50 transition duration-150 cursor-pointer ${!notification.is_read ? 'bg-blue-50' : 'bg-white'}"
                 onclick="markAsRead('${notification.id}', '${notification.action_url}')">
                <div class="p-6">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <i class="fas ${getIconClass(notification.type)} text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-gray-800">${escapeHtml(notification.message)}</p>
                            <div class="flex items-center justify-between mt-2">
                                <p class="text-xs text-gray-500">${notification.created_at}</p>
                                ${!notification.is_read ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">New</span>' : ''}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
            }

            function renderPagination(data) {
                const container = document.getElementById('pagination');
                if (!data.pagination || data.pagination.last_page <= 1) {
                    container.style.display = 'none';
                    return;
                }

                container.style.display = 'block';
                let paginationHtml = '<div class="flex justify-center space-x-2">';

                for (let i = 1; i <= data.pagination.last_page; i++) {
                    paginationHtml += `
                <button onclick="loadNotifications(${i})" 
                    class="px-3 py-1 rounded ${i === currentPage ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'}">
                    ${i}
                </button>
            `;
                }

                paginationHtml += '</div>';
                container.innerHTML = paginationHtml;
            }

            function markAsRead(notificationId, actionUrl) {
                fetch(`/notifications/${notificationId}/mark-as-read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                }).then(() => {
                    if (actionUrl) {
                        window.location.href = actionUrl;
                    } else {
                        loadNotifications(currentPage);
                        // Update badge count
                        if (window.updateNotificationBadge) {
                            window.updateNotificationBadge();
                        }
                    }
                }).catch(error => {
                    console.error('Error marking notification as read:', error);
                    if (actionUrl) {
                        window.location.href = actionUrl;
                    }
                });
            }

            function getIconClass(type) {
                const icons = {
                    'meeting': 'fa-calendar-alt text-blue-500',
                    'team': 'fa-users text-green-500',
                    'task': 'fa-tasks text-purple-500',
                    'bulk': 'fa-bullhorn text-yellow-500'
                };
                return icons[type] || 'fa-bell text-gray-500';
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML();
            }

            // Mark all as read
            document.getElementById('markAllRead')?.addEventListener('click', () => {
                fetch('/notifications/mark-all-as-read', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                }).then(() => {
                    loadNotifications(1);
                    if (window.updateNotificationBadge) {
                        window.updateNotificationBadge();
                    }
                });
            });

            // Load initial notifications
            loadNotifications(1);
        </script>
    @endpush
@endsection
