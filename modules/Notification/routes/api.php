<?php

use Illuminate\Support\Facades\Route;
use Modules\Notification\Http\Controllers\Api\ApiPushNotificationController;
use Modules\Notification\Http\Controllers\NotificationController;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('v1')->group(function () {
    // Notification Counts
    Route::get('notification/counts', [NotificationController::class, 'counts']);

    // Additional notification routes
    Route::patch('notification/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
    Route::patch('notification/{id}/mark-as-unread', [NotificationController::class, 'markAsUnread']);
    Route::patch('notification/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);

    // Only include index, show, and destroy routes
    Route::apiResource('notification', NotificationController::class)
        ->only(['index', 'show', 'destroy']);

    // Firebase token & FCM
    Route::post('firebase-token', [ApiPushNotificationController::class, 'updateFirebaseToken']);
    Route::post('notification/send', [ApiPushNotificationController::class, 'send'])->middleware('throttle:6,1');
});
