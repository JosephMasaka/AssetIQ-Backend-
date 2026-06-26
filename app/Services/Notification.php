<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Static facade for NotificationService
 * Provides convenient static methods for sending notifications
 */
class Notification
{
    protected static ?NotificationService $service = null;

    protected static function getService(): NotificationService
    {
        if (static::$service === null) {
            static::$service = app(NotificationService::class);
        }
        return static::$service;
    }

    /**
     * Send notification to a user
     */
    public static function send(
        User|int $user,
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        ?array $channels = null,
        ?Model $related = null
    ) {
        return static::getService()->send($user, $title, $message, $type, $data, $channels, $related);
    }

    /**
     * Send email notification
     */
    public static function email(User|int $user, string $title, string $message, array $data = [])
    {
        return static::getService()->sendEmail($user, $title, $message, $data);
    }

    /**
     * Send SMS notification
     */
    public static function sms(User|int $user, string $message)
    {
        return static::getService()->sendSms($user, $message);
    }

    /**
     * Send in-app notification
     */
    public static function inApp(
        User|int $user,
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        ?Model $related = null
    ) {
        return static::getService()->sendInApp($user, $title, $message, $type, $data, $related);
    }

    /**
     * Send to multiple users
     */
    public static function toMultiple(
        array $users,
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        ?array $channels = null,
        ?Model $related = null
    ) {
        return static::getService()->sendToMultiple($users, $title, $message, $type, $data, $channels, $related);
    }

    /**
     * Send to all users in a company
     */
    public static function toCompany(
        int $companyId,
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        ?array $channels = null,
        ?Model $related = null
    ) {
        return static::getService()->sendToCompany($companyId, $title, $message, $type, $data, $channels, $related);
    }

    /**
     * Get unread count for user
     */
    public static function unreadCount(User|int $user): int
    {
        return static::getService()->getUnreadCount($user);
    }

    /**
     * Mark notification as read
     */
    public static function markAsRead(int $notificationLogId): bool
    {
        return static::getService()->markAsRead($notificationLogId);
    }

    /**
     * Mark all user notifications as read
     */
    public static function markAllAsRead(User|int $user): int
    {
        return static::getService()->markAllAsRead($user);
    }
}
