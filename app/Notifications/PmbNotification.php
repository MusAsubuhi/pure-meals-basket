<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * Base notification for PMB customer updates.
 *
 * All customer-facing notifications funnel through this so the branding,
 * retry behaviour and channel selection stay consistent. By default they
 * are queued (ShouldQueue) so a slow mail provider never blocks a checkout.
 */
abstract class PmbNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * The channels the notification should be sent over.
     *
     * Mail is always on; SMS is opt-in and only when a phone number is set.
     */
    public function via(mixed $notifiable): array
    {
        $channels = ['mail'];

        if (! empty($notifiable->phone)) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    /**
     * Fallback when the mail driver is not configured — swallow instead of
     * breaking the orchestrator that triggered the notification.
     */
    public function failed(?object $exception): void
    {
        Log::warning('PMB notification failed', [
            'notification' => static::class,
            'exception' => $exception?->getMessage(),
        ]);
    }
}