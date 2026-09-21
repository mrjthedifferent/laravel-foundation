<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\NotificationController;
use Modules\Notification\Http\Controllers\PushNotificationController;

Route::middleware(['auth', 'verified'])->prefix('admin')->name('admin.')->group(function () {
    // Notification web views
    Route::resource('notification', NotificationController::class)
        ->only(['index', 'show', 'destroy'])
        ->names('notification');

    // Mark actions — shared route names used by blade views & API clients
    Route::patch('notification/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notification.mark-as-read');
    Route::patch('notification/{id}/mark-as-unread', [NotificationController::class, 'markAsUnread'])->name('notification.mark-as-unread');
    Route::patch('notification/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notification.mark-all-as-read');

    // Push notification web views
    Route::get('push-notifications', [PushNotificationController::class, 'index'])->name('push.notification.index');
    Route::get('push-notification/create', [PushNotificationController::class, 'create'])->name('push.notification.create');
    Route::post('push-notification/create', [PushNotificationController::class, 'store'])->name('push.notification.store');
});
