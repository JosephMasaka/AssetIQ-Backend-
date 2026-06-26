<?php

namespace App\Services;

use App\Models\User;
use App\Models\NotificationLog;
use App\Models\SmsLog;
use App\Models\EmailLog;
use App\Models\NotificationPreference;
use App\Notifications\AppNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Database\Eloquent\Model;
use Exception;

class NotificationService
{
    /**
     * Send notification to a user via multiple channels
     *
     * @param User|int $user User model or user ID
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type (system, alert, warning, info, success)
     * @param array $data Additional data (action_url, action_text, additional_lines, etc.)
     * @param array|null $channels Override channels (database, mail, sms) - null uses user preferences
     * @param Model|null $related Related model for polymorphic relation
     * @return NotificationLog
     */
    public function send(
        User|int $user,
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        ?array $channels = null,
        ?Model $related = null
    ): NotificationLog {
        // Resolve user if ID provided
        if (is_int($user)) {
            $user = User::findOrFail($user);
        }

        // Get user's company_id
        $companyId = $this->getCompanyId($user);

        // Determine channels based on preferences if not explicitly provided
        if ($channels === null) {
            $channels = $this->getEnabledChannels($user, $type);
        }

        // Filter out disabled channels
        $channels = $this->filterChannels($channels);

        // Create notification log entry
        $notificationLog = $this->createNotificationLog(
            $user,
            $title,
            $message,
            $type,
            $data,
            $channels,
            $companyId,
            $related
        );

        // Send notification via Laravel's notification system
        try {
            if (!empty($channels)) {
                $notification = new AppNotification($title, $message, $type, $data, $channels);
                $user->notify($notification);

                // Log successful send
                $notificationLog->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                // Create channel-specific logs
                $this->createChannelLogs($user, $title, $message, $data, $channels, $companyId);
            }
        } catch (Exception $e) {
            // Log failure
            $notificationLog->update([
                'status' => 'failed',
                'data' => array_merge($data, ['error' => $e->getMessage()]),
            ]);

            // Re-throw or handle as needed
            \Log::error('Notification send failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $notificationLog;
    }

    /**
     * Send notification to multiple users
     *
     * @param array $users Array of User models or user IDs
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $data
     * @param array|null $channels
     * @param Model|null $related
     * @return array Array of NotificationLog instances
     */
    public function sendToMultiple(
        array $users,
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        ?array $channels = null,
        ?Model $related = null
    ): array {
        $logs = [];

        foreach ($users as $user) {
            $logs[] = $this->send($user, $title, $message, $type, $data, $channels, $related);
        }

        return $logs;
    }

    /**
     * Send notification to all users in a company
     *
     * @param int $companyId
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $data
     * @param array|null $channels
     * @param Model|null $related
     * @return array
     */
    public function sendToCompany(
        int $companyId,
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        ?array $channels = null,
        ?Model $related = null
    ): array {
        $users = User::where('tenant_id', $companyId)
            ->orWhere('created_by', $companyId)
            ->where('is_active', true)
            ->get();

        return $this->sendToMultiple($users->all(), $title, $message, $type, $data, $channels, $related);
    }

    /**
     * Send email notification
     *
     * @param User|int $user
     * @param string $title
     * @param string $message
     * @param array $data
     * @return NotificationLog
     */
    public function sendEmail(
        User|int $user,
        string $title,
        string $message,
        array $data = []
    ): NotificationLog {
        return $this->send($user, $title, $message, 'info', $data, ['mail']);
    }

    /**
     * Send SMS notification
     *
     * @param User|int $user
     * @param string $message
     * @return NotificationLog
     */
    public function sendSms(
        User|int $user,
        string $message
    ): NotificationLog {
        return $this->send($user, 'SMS Notification', $message, 'info', [], ['sms']);
    }

    /**
     * Send in-app notification only
     *
     * @param User|int $user
     * @param string $title
     * @param string $message
     * @param string $type
     * @param array $data
     * @param Model|null $related
     * @return NotificationLog
     */
    public function sendInApp(
        User|int $user,
        string $title,
        string $message,
        string $type = 'info',
        array $data = [],
        ?Model $related = null
    ): NotificationLog {
        return $this->send($user, $title, $message, $type, $data, ['database'], $related);
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationLogId
     * @return bool
     */
    public function markAsRead(int $notificationLogId): bool
    {
        $notification = NotificationLog::find($notificationLogId);

        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Mark all user notifications as read
     *
     * @param User|int $user
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead(User|int $user): int
    {
        if (is_int($user)) {
            $user = User::findOrFail($user);
        }

        return NotificationLog::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Get user's unread notification count
     *
     * @param User|int $user
     * @return int
     */
    public function getUnreadCount(User|int $user): int
    {
        if (is_int($user)) {
            $user = User::findOrFail($user);
        }

        return NotificationLog::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Get user's notifications
     *
     * @param User|int $user
     * @param bool $unreadOnly
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserNotifications(User|int $user, bool $unreadOnly = false, int $limit = 50)
    {
        if (is_int($user)) {
            $user = User::findOrFail($user);
        }

        $query = NotificationLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        if ($unreadOnly) {
            $query->unread();
        }

        return $query->get();
    }

    /**
     * Update or create notification preference
     *
     * @param User|int $user
     * @param string $notificationType
     * @param bool $emailEnabled
     * @param bool $databaseEnabled
     * @param bool $smsEnabled
     * @param array $customSettings
     * @return NotificationPreference
     */
    public function setPreference(
        User|int $user,
        string $notificationType,
        bool $emailEnabled = true,
        bool $databaseEnabled = true,
        bool $smsEnabled = false,
        array $customSettings = []
    ): NotificationPreference {
        if (is_int($user)) {
            $user = User::findOrFail($user);
        }

        return NotificationPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => $notificationType,
            ],
            [
                'email_enabled' => $emailEnabled,
                'database_enabled' => $databaseEnabled,
                'sms_enabled' => $smsEnabled,
                'custom_settings' => $customSettings,
            ]
        );
    }

    /**
     * Get user's notification preferences
     *
     * @param User|int $user
     * @param string|null $notificationType
     * @return NotificationPreference|\Illuminate\Database\Eloquent\Collection|null
     */
    public function getPreferences(User|int $user, ?string $notificationType = null)
    {
        if (is_int($user)) {
            $user = User::findOrFail($user);
        }

        if ($notificationType) {
            return NotificationPreference::where('user_id', $user->id)
                ->where('notification_type', $notificationType)
                ->first();
        }

        return NotificationPreference::where('user_id', $user->id)->get();
    }

    /**
     * Create notification log entry
     */
    protected function createNotificationLog(
        User $user,
        string $title,
        string $message,
        string $type,
        array $data,
        array $channels,
        int $companyId,
        ?Model $related
    ): NotificationLog {
        $logData = [
            'user_id' => $user->id,
            'notification_type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'channels' => $channels,
            'status' => 'queued',
            'company_id' => $companyId,
        ];

        if ($related) {
            $logData['related_type'] = get_class($related);
            $logData['related_id'] = $related->id;
        }

        return NotificationLog::create($logData);
    }

    /**
     * Create channel-specific logs
     */
    protected function createChannelLogs(
        User $user,
        string $title,
        string $message,
        array $data,
        array $channels,
        int $companyId
    ): void {
        if (in_array('mail', $channels)) {
            EmailLog::create([
                'user_id' => $user->id,
                'to_email' => $user->email,
                'subject' => $title,
                'body' => $message,
                'status' => 'sent',
                'metadata' => $data,
                'company_id' => $companyId,
                'sent_at' => now(),
            ]);
        }

        if (in_array('sms', $channels) && $user->phone) {
            SmsLog::create([
                'user_id' => $user->id,
                'phone_number' => $user->phone,
                'message' => $message,
                'status' => 'sent',
                'metadata' => $data,
                'company_id' => $companyId,
                'sent_at' => now(),
            ]);
        }
    }

    /**
     * Get enabled channels for user based on preferences
     */
    protected function getEnabledChannels(User $user, string $type): array
    {
        $preference = NotificationPreference::where('user_id', $user->id)
            ->where('notification_type', $type)
            ->first();

        if ($preference) {
            return $preference->getEnabledChannels();
        }

        // Default channels if no preference set
        return ['database', 'mail'];
    }

    /**
     * Filter channels to only include supported ones
     */
    protected function filterChannels(array $channels): array
    {
        $supported = ['database', 'mail', 'sms'];
        return array_intersect($channels, $supported);
    }

    /**
     * Get company ID from user
     */
    protected function getCompanyId(User $user): int
    {
        // Follow the pattern from User model's getCompany method
        if ($user->role === 'company') {
            return $user->id;
        }

        return $user->created_by ?? $user->tenant_id ?? $user->id;
    }
}
