<?php

namespace App\Notifications;

use App\Mail\OrderCompletedMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;

class OrderCompleted extends PmbNotification
{
    use Queueable;

    public function __construct(
        public Order $order,
    ) {}

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable)
    {
        return (new OrderCompletedMail($this->order))->to($notifiable->email, $notifiable->name);
    }
}