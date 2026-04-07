<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FbrNoticeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProcessController;
use App\Http\Controllers\ProceedingController;
use App\Http\Controllers\AutomatedTaskController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ClientDocumentController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\MicrosoftAuthController;

Route::get('/', function () {
    return redirect('/login');
});

// Chrome Extension API (token-based auth)
Route::post('ext/login', [\App\Http\Controllers\CredentialApiController::class, 'login'])->name('ext.login');
Route::get('ext/clients', [\App\Http\Controllers\CredentialApiController::class, 'searchClients'])->name('ext.clients');
Route::get('ext/credentials/{client}', [\App\Http\Controllers\CredentialApiController::class, 'getCredentials'])->name('ext.credentials');

Route::get('/test-deploy', function () {
    return 'Deploy working! ' . now();
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', ClientController::class);

    Route::resource('tasks', TaskController::class);
    Route::post('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');

    Route::resource('employees', EmployeeController::class);

    Route::get('fbr-notices/fetch-now', [FbrNoticeController::class, 'fetchNow'])->name('fbr-notices.fetch');
    Route::resource('fbr-notices', FbrNoticeController::class, ['only' => ['index', 'show']]);
    Route::post('fbr-notices/{fbr_notice}/status', [FbrNoticeController::class, 'updateStatus'])->name('fbr-notices.updateStatus');
    Route::post('fbr-notices/{fbr_notice}/escalate', [FbrNoticeController::class, 'escalate'])->name('fbr-notices.escalate');
    Route::post('fbr-notices/{fbr_notice}/assign-client', [FbrNoticeController::class, 'assignClient'])->name('fbr-notices.assignClient');
    Route::post('fbr-notices/{fbr_notice}/dismiss', [FbrNoticeController::class, 'dismiss'])->name('fbr-notices.dismiss');
    Route::post('fbr-notices/{fbr_notice}/mark-read', [FbrNoticeController::class, 'markRead'])->name('fbr-notices.markRead');
    Route::get('fbr-notices/{fbr_notice}/download', [FbrNoticeController::class, 'download'])->name('fbr-notices.download');

    // Processes
    Route::resource('processes', ProcessController::class);
    Route::post('processes/{process}/stage', [ProcessController::class, 'updateStage'])->name('processes.updateStage');

    // Pending Proceedings
    Route::resource('proceedings', ProceedingController::class)->parameters(['proceedings' => 'proceeding']);

    // Scheduled Tasks (Settings)
    Route::resource('scheduled-tasks', AutomatedTaskController::class)->except(['show'])->parameters(['scheduled-tasks' => 'automated_task']);
    Route::post('scheduled-tasks/{automated_task}/toggle', [AutomatedTaskController::class, 'toggle'])->name('scheduled-tasks.toggle');
    Route::post('scheduled-tasks/{automated_task}/run', [AutomatedTaskController::class, 'runNow'])->name('scheduled-tasks.run');

    // File Management
    Route::get('file-management', [FileController::class, 'index'])->name('files.index');
    Route::post('file-management/files', [FileController::class, 'storeFile'])->name('files.store-file');
    Route::post('file-management/letters', [FileController::class, 'storeLetter'])->name('files.store-letter');
    Route::delete('file-management/files/{fileNumber}', [FileController::class, 'destroyFile'])->name('files.destroy-file');
    Route::delete('file-management/letters/{letterNumber}', [FileController::class, 'destroyLetter'])->name('files.destroy-letter');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::get('notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

    // Settings
    Route::get('settings/email', [SettingsController::class, 'email'])->name('settings.email');
    Route::post('settings/email/update-sender', [SettingsController::class, 'updateSender'])->name('settings.email.update-sender');

    // Microsoft Auth
    Route::get('auth/microsoft', [MicrosoftAuthController::class, 'redirect'])->name('auth.microsoft.redirect');
    Route::get('auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])->name('auth.microsoft.callback');
    Route::post('auth/microsoft/disconnect', [MicrosoftAuthController::class, 'disconnect'])->name('auth.microsoft.disconnect');
    Route::get('auth/microsoft/test', [MicrosoftAuthController::class, 'testFetch'])->name('auth.microsoft.test');
    Route::get('auth/microsoft/refresh', [MicrosoftAuthController::class, 'refreshToken'])->name('auth.microsoft.refresh');

    // Client Documents
    Route::get('client-documents', [ClientDocumentController::class, 'index'])->name('client-documents.index');
    Route::post('client-documents/{client}/update-link', [ClientDocumentController::class, 'updateLink'])->name('client-documents.update-link');
    Route::post('client-documents/link-folder', [ClientDocumentController::class, 'linkFolder'])->name('client-documents.link-folder');

    // News
    Route::get('news', [NewsController::class, 'index'])->name('news.index');
    Route::get('news/fetch', [NewsController::class, 'fetchNow'])->name('news.fetch');
    Route::post('news/{newsArticle}/pin', [NewsController::class, 'togglePin'])->name('news.pin');
    Route::delete('news/{newsArticle}', [NewsController::class, 'destroy'])->name('news.destroy');

    // Comments
    Route::post('comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

    // Search
    Route::get('search', [SearchController::class, 'index'])->name('search');
    Route::get('search/suggest', [SearchController::class, 'suggest'])->name('search.suggest');

    Route::get('/mini-apps', function () {
        return view('mini-apps.index');
    })->name('mini-apps.index');

    // Chrome Extension
    Route::get('extension', function () { return view('extension.download'); })->name('extension.download');
});

require __DIR__.'/auth.php';
