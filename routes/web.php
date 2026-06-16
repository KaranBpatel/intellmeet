<?php

use App\Http\Controllers\AIController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


// =============================================
// PUBLIC ROUTES (no authentication required)
// =============================================

// Guest routes (redirect if already authenticated)
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');

    // Password reset routes
    Route::get('/forgot-password', [AuthController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Home redirect
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// =============================================
// AUTHENTICATED ROUTES
// =============================================
Route::middleware(['auth'])->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/meetings/analytics', [DashboardController::class, 'meetingAnalytics'])->name('meetings.analytics');

    // ========== NOTIFICATION ROUTES ==========
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/json', [NotificationController::class, 'getNotifications'])->name('json');
        Route::post('/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count', [NotificationController::class, 'getUnreadCount'])->name('unread-count');
    });

    // ========== MEETING ROUTES ==========
    Route::get('/meetings/popup/{meeting}', [MeetingController::class, 'popup'])->name('meetings.popup');
    Route::get('/join/{code}', [MeetingController::class, 'joinByCode'])->name('meetings.join.by-code');
    Route::get('/meetings/video-room/{meeting}', [MeetingController::class, 'videoRoom'])->name('meetings.video-room');
    Route::get('/meetings/participant-name/{userId}', [MeetingController::class, 'getParticipantName'])->name('meetings.participant-name');
    Route::get('/meetings/join/{meeting}', [MeetingController::class, 'join'])->name('meetings.join');
    Route::post('/meetings/end/{meeting}', [MeetingController::class, 'end'])->name('meetings.end');
    Route::get('/meetings/invite-link/{meeting}', [MeetingController::class, 'getInviteLink'])->name('meetings.invite-link');
    Route::post('/meetings/add-participant/{meeting}', [MeetingController::class, 'addParticipant'])->name('meetings.add-participant');
    Route::patch('/action-items/{id}/complete', [MeetingController::class, 'markActionComplete'])->name('action-items.complete');
    Route::resource('meetings', MeetingController::class);

    // ========== TASK ROUTES ==========
    Route::get('/my-tasks', [TaskController::class, 'myTasks'])->name('my-tasks');
    Route::get('/api/tasks', [TaskController::class, 'getTasks'])->name('api.tasks');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.update-status');
    Route::resource('tasks', TaskController::class);
    Route::post('/tasks/{task}/attachments', [TaskController::class, 'uploadAttachment'])->name('tasks.upload-attachment');
    Route::delete('/tasks/{task}/attachments/{attachment}', [TaskController::class, 'deleteAttachment'])->name('tasks.delete-attachment');
    Route::get('/tasks/{task}/attachments/{attachment}/download', [TaskController::class, 'downloadAttachment'])->name('tasks.download-attachment');

    // ========== TEAM ROUTES ==========
    Route::get('/teams/{team}/members-json', [TeamController::class, 'getMembersJson'])->name('teams.members.json');
    Route::get('/teams/{team}/members', [TeamController::class, 'members'])->name('teams.members');
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.addMember');
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.removeMember');
    Route::patch('/teams/{team}/members/{user}/role', [TeamController::class, 'updateMemberRole'])->name('teams.updateMemberRole');
    Route::post('/teams/{team}/invite', [TeamController::class, 'inviteMember'])->name('teams.invite');
    Route::delete('/teams/{team}/leave', [TeamController::class, 'leave'])->name('teams.leave');
    Route::post('/teams/{team}/transfer', [TeamController::class, 'transferOwnership'])->name('teams.transfer');
    Route::resource('teams', TeamController::class);
    Route::get('/invite/{token}', [TeamController::class, 'acceptInvite'])->name('teams.acceptInvite');

    // ========== PROFILE ROUTES ==========
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ========== SETTINGS ROUTES ==========
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.update-profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.update-password');
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.update-notifications');
    Route::post('/settings/avatar', [SettingsController::class, 'uploadAvatar'])->name('settings.upload-avatar');
    Route::delete('/settings/avatar', [SettingsController::class, 'removeAvatar'])->name('settings.remove-avatar');

    // ========== AI ROUTES ==========
    Route::prefix('ai')->middleware(['auth'])->name('ai.')->group(function () {
        Route::get('/insights', [AIController::class, 'getInsights'])->name('insights');
        Route::post('/meetings/{meeting}/process', [AIController::class, 'processMeeting'])->name('process');
        Route::post('/meetings/{meeting}/process-text', [AIController::class, 'processText'])->name('process-text');
        Route::get('/meetings/{meeting}/insights', [AIController::class, 'showMeetingInsights'])->name('meetings.insights');
        Route::post('/chat', [AIController::class, 'chatAssistant'])->name('chat');
    });

    // ========== ADMIN ROUTES ==========
    Route::prefix('admin')->name('admin.')->middleware(['admin'])->group(function () {
        Route::resource('users', UserController::class);
        Route::post('/users/{user}/restore', [UserController::class, 'restore'])->name('users.restore');
        Route::delete('/users/{user}/force', [UserController::class, 'forceDelete'])->name('users.forceDelete');
        Route::post('/users/bulk-delete', [UserController::class, 'bulkDelete'])->name('users.bulkDelete');
        Route::post('/users/bulk-restore', [UserController::class, 'bulkRestore'])->name('users.bulkRestore');
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
        Route::post('/users/{user}/change-role', [UserController::class, 'changeRole'])->name('users.changeRole');
    });
});

// =============================================
// PUBLIC UTILITY ROUTES
// =============================================
Route::get('/check-auth', function () {
    return response()->json(['authenticated' => Auth::check()]);
})->name('check.auth');

Route::get('/test-session', function () {
    session(['test' => 'working']);
    return '<a href="/test-session-check">Check session</a>';
});

Route::get('/test-session-check', function () {
    return 'Session value: ' . (session('test', 'NOT SET - Session failed!'));
});

// Pulse dashboard
Route::get('/pulse', function () {
    return view('pulse::dashboard');
})->middleware(['auth', 'can:viewPulse']); 
  Route::get('/debug-log', function () {
    return nl2br(file_get_contents(storage_path('logs/laravel.log')));
});
