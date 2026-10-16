<?php

namespace App\Notifications;

use App\Mail\OrderConfirmedMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;

class OrderConfirmed extends PmbNotification
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
        return (new OrderConfirmedMail($this->order))->to($notifiable->email, $notifiable->name);
    }
}