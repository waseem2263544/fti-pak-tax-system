<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FbrNoticeController;
use App\Http\Controllers\NotificationController;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Clients
    Route::resource('clients', ClientController::class);

    // Tasks
    Route::resource('tasks', TaskController::class);
    Route::post('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');

    // Employees
    Route::resource('employees', EmployeeController::class);

    // FBR Notices
    Route::resource('fbr-notices', FbrNoticeController::class, ['only' => ['index', 'show']]);
    Route::post('fbr-notices/{fbr_notice}/status', [FbrNoticeController::class, 'updateStatus'])->name('fbr-notices.updateStatus');
    Route::post('fbr-notices/{fbr_notice}/escalate', [FbrNoticeController::class, 'escalate'])->name('fbr-notices.escalate');
    Route::post('fbr-notices/{fbr_notice}/assign-client', [FbrNoticeController::class, 'assignClient'])->name('fbr-notices.assignClient');
    Route::get('fbr-notices/{fbr_notice}/download', [FbrNoticeController::class, 'download'])->name('fbr-notices.download');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::get('notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

    // Mini Apps
    Route::get('/mini-apps', function () {
        return view('mini-apps.index');
    })->name('mini-apps.index');
});

require __DIR__.'/auth.php';
