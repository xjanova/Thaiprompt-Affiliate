<?php

use Illuminate\Support\Facades\Broadcast;

/**
 * User Model Channel
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Notification Channels
 *
 * Authorization สำหรับ private notification channels
 */

// Notification Bell Channel - ระบบแจ้งเตือนหลัก
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Immediate Notification Channel - Toast popup
Broadcast::channel('notifications.immediate.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
